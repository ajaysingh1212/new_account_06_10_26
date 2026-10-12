const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/stocks/index.blade.php'), 'utf8');
const functionSource = view.slice(view.indexOf('function updateStockTotals()'), view.indexOf("stockTable.on('draw', updateStockTotals)"));

function summary(allRows, matchingRows, serverFiltered = false) {
    const output = {};
    const context = {
        stockHasServerFilters: serverFiltered,
        stockTable: {
            rows(options) {
                // A default DataTables selection spans every page.
                if (options) {
                    assert.equal(options.search, 'applied');
                    assert.equal(options.page, 'all');
                }
                const rows = options ? matchingRows : allRows;
                return {
                    count: () => rows.length,
                    every(callback) {
                        rows.forEach(row => callback.call({node: () => ({dataset: row})}));
                    },
                };
            },
        },
        $: selector => ({text: value => { output[selector] = value; }}),
    };
    // Evaluate the actual function shipped by the Blade view.
    vm.runInNewContext(functionSource + '\nupdateStockTotals();', context);
    return output;
}

const filtered = [
    [850000, 100000], [300000, 1000000], [200000, 1000000],
    [240000, 800000], [750000, 500000], [750000, 500000],
    [100000, 500000], [100000, 500000], [275000, 500000],
    [200000, 1000000], [200000, 1000000], [400000, 2000000], [500000, 1000000],
].map(([stockValuePaise, stockQuantityMilli]) => ({stockValuePaise, stockQuantityMilli}));
const all = [...filtered, {stockValuePaise: 2470000, stockQuantityMilli: 130000}];

test('search totals include every matching row across pages, and clearing search restores overall totals', () => {
    const result = summary(all, filtered);
    assert.equal(result['#stockTotalValue'], 'Rs 48,650.00');
    assert.equal(result['#stockTotalQuantity'], '10,400.000');
    assert.equal(result['#stockValueLabel'], 'Filtered Stock Value');
    assert.equal(result['#stockTotalsScope'], '13 matching items (all pages)');
    const reset = summary(all, all);
    assert.equal(reset['#stockTotalValue'], 'Rs 73,350.00');
    assert.equal(reset['#stockTotalQuantity'], '10,530.000');
    assert.equal(reset['#stockValueLabel'], 'Overall Stock Value');
});

test('no matches show zero and server filters retain the filtered label', () => {
    assert.equal(summary(all, [])['#stockTotalValue'], 'Rs 0.00');
    assert.equal(summary(all, [])['#stockTotalQuantity'], '0.000');
    assert.equal(summary(filtered, filtered, true)['#stockValueLabel'], 'Filtered Stock Value');
});

test('totals add rounded paise and fractional quantities without parsing display text', () => {
    const rows = [
        {stockValuePaise: 34, stockQuantityMilli: 333},
        {stockValuePaise: 34, stockQuantityMilli: 333},
    ];
    assert.equal(summary(rows, rows)['#stockTotalValue'], 'Rs 0.68');
    assert.equal(summary(rows, rows)['#stockTotalQuantity'], '0.666');
});
