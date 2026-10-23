const MAX_PRODUCTS_PER_REQUEST = 100;
const POLL_INTERVAL_CONNECTED = 60_000;
const POLL_INTERVAL_DISCONNECTED = 15_000;
const STOCK_TOLERANCE = 0.0005;
const quantityFormatter = new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 3,
});

export function formatQty(value) {
    return quantityFormatter.format(Number(value) || 0);
}

export class StockRealtime {
    constructor({ warehouseId = null, endpoint = null } = {}) {
        this.warehouseId = Number(warehouseId) || null;
        this.endpoint = endpoint;
        this.levels = new Map();
        this.versions = new Map();
        this.watchers = new Map();
        this.connected = false;
        this.started = false;
        this.polling = false;
        this.pollTimer = null;
        this.channel = null;
    }

    watch(productId, initialAvailable, listener) {
        const id = Number(productId);

        if (!Number.isInteger(id) || id <= 0) {
            return () => {};
        }

        if (!this.levels.has(id)) {
            this.levels.set(id, this.normalize(initialAvailable));
        }

        if (!this.watchers.has(id)) {
            this.watchers.set(id, new Set());
        }

        this.watchers.get(id).add(listener);
        this.start();

        const cached = this.levels.get(id);
        const initial = this.normalize(initialAvailable);

        if (cached !== initial) {
            queueMicrotask(() => listener(cached));
        }

        return () => {
            const listeners = this.watchers.get(id);
            listeners?.delete(listener);

            if (listeners?.size === 0) {
                this.watchers.delete(id);
            }
        };
    }

    start() {
        if (this.started || !this.warehouseId) {
            return;
        }

        this.started = true;
        this.subscribe();
        this.bindConnection();
        this.bindVisibility();
        this.poll();
        this.schedule();
    }

    subscribe() {
        if (!window.Echo) {
            return;
        }

        this.channel = window.Echo.channel(`stock.${this.warehouseId}`);
        this.channel.listen('.stock.updated', (payload) => {
            this.apply(payload.product_id, payload.available, payload.version);
        });
    }

    bindConnection() {
        const connection = window.Echo?.connector?.pusher?.connection;

        if (!connection) {
            return;
        }

        const sync = () => {
            const connected = connection.state === 'connected';

            if (connected === this.connected) {
                return;
            }

            this.connected = connected;
            this.poll();
            this.schedule();
        };

        connection.bind('state_change', sync);
        connection.bind('connected', sync);
        connection.bind('disconnected', sync);
        connection.bind('unavailable', sync);
        sync();
    }

    bindVisibility() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                return;
            }

            this.poll();
            this.schedule();
        });
    }

    schedule() {
        window.clearTimeout(this.pollTimer);

        this.pollTimer = window.setTimeout(async () => {
            if (!document.hidden) {
                await this.poll();
            }

            this.schedule();
        }, this.connected ? POLL_INTERVAL_CONNECTED : POLL_INTERVAL_DISCONNECTED);
    }

    async poll() {
        if (this.polling || !this.endpoint || this.levels.size === 0) {
            return;
        }

        this.polling = true;

        try {
            const productIds = Array.from(this.levels.keys());

            for (let offset = 0; offset < productIds.length; offset += MAX_PRODUCTS_PER_REQUEST) {
                const chunk = productIds.slice(offset, offset + MAX_PRODUCTS_PER_REQUEST);
                const url = new URL(this.endpoint, window.location.origin);
                url.searchParams.set('ids', chunk.join(','));

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    continue;
                }

                const payload = await response.json();

                for (const level of payload.data ?? []) {
                    this.apply(level.product_id, level.available, payload.version);
                }
            }
        } catch {
            // Polling is best-effort; the next interval or reconnect retries.
        } finally {
            this.polling = false;
        }
    }

    apply(productId, available, version = null) {
        const id = Number(productId);

        if (!Number.isInteger(id) || id <= 0) {
            return;
        }

        const nextVersion = Number(version) || 0;
        const previousVersion = this.versions.get(id) ?? 0;

        if (nextVersion > 0 && nextVersion < previousVersion) {
            return;
        }

        if (nextVersion > 0) {
            this.versions.set(id, nextVersion);
        }

        const normalized = this.normalize(available);
        this.levels.set(id, normalized);

        for (const listener of this.watchers.get(id) ?? []) {
            listener(normalized);
        }
    }

    normalize(value) {
        return Math.max(0, Number(value) || 0);
    }
}

function productState(productId, initialAvailable) {
    return {
        productId: Number(productId),
        available: Math.max(0, Number(initialAvailable) || 0),
        init() {
            this.stopStock = window.stockRealtime?.watch(
                this.productId,
                this.available,
                (available) => {
                    this.available = available;
                },
            );
        },
        destroy() {
            this.stopStock?.();
        },
        fmt(value) {
            return formatQty(value);
        },
    };
}

export function stockCard(productId, initialAvailable) {
    const state = productState(productId, initialAvailable);

    Object.defineProperty(state, 'stockText', {
        get() {
            return this.available > STOCK_TOLERANCE
                ? `Stok ${formatQty(this.available)}`
                : 'Stok habis';
        },
    });

    return state;
}

export function productStock(productId, initialAvailable, unit) {
    const state = productState(productId, initialAvailable);
    state.unit = unit;

    Object.defineProperty(state, 'stockText', {
        get() {
            return this.available > STOCK_TOLERANCE
                ? `Stok tersedia ${formatQty(this.available)} ${this.unit}`
                : 'Stok habis';
        },
    });

    return state;
}

function lineState(lines) {
    const state = {
        lines: (lines ?? []).map((line) => ({
            productId: Number(line.product_id),
            quantity: Number(line.quantity) || 0,
            available: Math.max(0, Number(line.available) || 0),
        })),
        init() {
            this.stopStock = this.lines
                .map((line) =>
                    window.stockRealtime?.watch(line.productId, line.available, (available) => {
                        line.available = available;
                    }),
                )
                .filter(Boolean);
        },
        destroy() {
            this.stopStock?.forEach((stop) => stop());
        },
        availableFor(productId) {
            return (
                this.lines.find((line) => line.productId === Number(productId))?.available ?? 0
            );
        },
        insufficientFor(productId) {
            const line = this.lines.find((entry) => entry.productId === Number(productId));

            return Boolean(
                line && line.quantity > line.available + STOCK_TOLERANCE,
            );
        },
        fmt(value) {
            return formatQty(value);
        },
    };

    Object.defineProperty(state, 'anyInsufficient', {
        get() {
            return this.lines.some(
                (line) => line.quantity > line.available + STOCK_TOLERANCE,
            );
        },
    });

    return state;
}

export function cartStock(lines) {
    return lineState(lines);
}

export function checkoutStock(delivery, lines) {
    return Object.assign(lineState(lines), { delivery });
}
