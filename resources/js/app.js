import Alpine from 'alpinejs';

import './echo';
import {
    StockRealtime,
    cartStock,
    checkoutStock,
    productStock,
    stockCard,
} from './stock';

const warehouseId = document.querySelector('meta[name="stock-warehouse-id"]')?.content;
const endpoint = document.querySelector('meta[name="stock-endpoint"]')?.content;

window.stockRealtime = new StockRealtime({ warehouseId, endpoint });
window.Alpine = Alpine;

Alpine.data('stockCard', stockCard);
Alpine.data('productStock', productStock);
Alpine.data('cartStock', cartStock);
Alpine.data('checkoutStock', checkoutStock);

Alpine.start();
