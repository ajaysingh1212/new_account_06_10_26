(function ($) {
    'use strict';

    const highlightedTables = new WeakMap();
    const ignoredElements = 'script, style, textarea, input, select, option, [hidden], [aria-hidden="true"], .d-none';

    function searchPattern(search) {
        if (!search || !search.sSearch || !search.sSearch.trim()) return null;
        const flags = search.bCaseInsensitive === false ? 'g' : 'gi';
        if (search.bRegex) {
            try { return new RegExp(search.sSearch, flags); } catch (_) { return null; }
        }
        const terms = search.bSmart === false
            ? [search.sSearch]
            : (search.sSearch.match(/"[^"]+"|“[^”]+”|\S+/g) || []).map(term => term.replace(/^["“]|["”]$/g, ''));
        const escaped = [...new Set(terms.filter(Boolean))]
            .sort((a, b) => b.length - a.length)
            .map(term => term.replace(/[.*+?^{}$()|[\]\\]/g, '\\$&'));
        return escaped.length ? new RegExp(escaped.join('|'), flags) : null;
    }

    function clearHighlights(table) {
        const marks = highlightedTables.get(table) || [];
        const parents = new Set();
        marks.forEach(mark => {
            if (!mark.parentNode) return;
            parents.add(mark.parentNode);
            mark.replaceWith(document.createTextNode(mark.textContent));
        });
        parents.forEach(parent => parent.normalize());
        const marksForDraw = [];
        highlightedTables.set(table, marksForDraw);
        return marksForDraw;
    }

    function highlightCell(cell, patterns, marks) {
        if (!cell || !patterns.length) return;
        const visibility = new WeakMap();
        function isVisible(element) {
            if (!element || element === cell.parentElement) return true;
            if (visibility.has(element)) return visibility.get(element);
            const style = window.getComputedStyle(element);
            const visible = !element.matches(ignoredElements)
                && style.display !== 'none' && style.visibility !== 'hidden'
                && isVisible(element.parentElement);
            visibility.set(element, visible);
            return visible;
        }
        const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT, {
            acceptNode: node => node.nodeValue.trim() && isVisible(node.parentElement)
                ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT
        });
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(node => {
            const value = node.nodeValue;
            const ranges = [];
            patterns.forEach(pattern => {
                pattern.lastIndex = 0;
                let match;
                while ((match = pattern.exec(value)) !== null) {
                    if (!match[0].length) { pattern.lastIndex++; continue; }
                    ranges.push([match.index, match.index + match[0].length]);
                }
            });
            if (!ranges.length) return;
            ranges.sort((a, b) => a[0] - b[0] || b[1] - a[1]);
            const merged = [];
            ranges.forEach(range => {
                const previous = merged[merged.length - 1];
                if (previous && range[0] < previous[1]) previous[1] = Math.max(previous[1], range[1]);
                else merged.push(range);
            });
            const fragment = document.createDocumentFragment();
            let offset = 0;
            merged.forEach(([start, end]) => {
                fragment.append(document.createTextNode(value.slice(offset, start)));
                const mark = document.createElement('mark');
                mark.className = 'datatable-search-highlight';
                mark.textContent = value.slice(start, end);
                fragment.append(mark);
                marks.push(mark);
                offset = end;
            });
            fragment.append(document.createTextNode(value.slice(offset)));
            node.replaceWith(fragment);
        });
    }

    $(document).on('destroy.dt.searchHighlight', function (event, settings) {
        if (settings && settings.nTable) {
            clearHighlights(settings.nTable);
            highlightedTables.delete(settings.nTable);
        }
    });

    // A delegated hook covers every current and future DataTable, including AJAX redraws.
    $(document).on('draw.dt.searchHighlight init.dt.searchHighlight', function (event, settings) {
        if (!settings || !settings.nTable) return;
        const api = new $.fn.dataTable.Api(settings);
        const marks = clearHighlights(settings.nTable);
        const globalPattern = searchPattern(settings.oPreviousSearch);
        api.columns().every(function (index) {
            if (!settings.aoColumns[index].bSearchable || !this.visible()) return;
            const patterns = [globalPattern, searchPattern(settings.aoPreSearchCols[index])].filter(Boolean);
            api.cells(null, index, {page: 'current', search: 'applied'}).nodes().each(cell => highlightCell(cell, patterns, marks));
        });
    });
})(jQuery);
