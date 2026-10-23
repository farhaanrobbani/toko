import Alpine from 'alpinejs';

import './echo';
import { StockRealtime } from './stock';

const warehouseId = document.querySelector('meta[name="stock-warehouse-id"]')?.content;
const endpoint = document.querySelector('meta[name="stock-endpoint"]')?.content;

window.stockRealtime = new StockRealtime({ warehouseId, endpoint });
window.Alpine = Alpine;

Alpine.start();
