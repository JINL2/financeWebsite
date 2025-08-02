/**
 * Phase 5.5: Advanced Search and Filtering JavaScript Functions
 */

// Global search state variables
let currentSearchFilters = {};
let currentSortBy = 'created_at';
let currentSortOrder = 'desc';

// Phase 5.5: Advanced Search Functions
function toggleAdvancedFilters() {
    const filtersContent = document.getElementById('advanced-filters-content');
    const toggleBtn = document.getElementById('toggle-filters-btn');
    
    if (filtersContent.style.display === 'none') {
        filtersContent.style.display = 'block';
        toggleBtn.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Hide Filters';
    } else {
        filtersContent.style.display = 'none';
        toggleBtn.innerHTML = '<i class="bi bi-chevron-down me-1"></i>Show Filters';
    }
}

function applyRealTimeSearch() {
    const searchText = document.getElementById('search-description').value.trim();
    
    // Debounce search to avoid too many API calls
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        if (searchText.length >= 2 || searchText.length === 0) {
            performAdvancedSearch();
        }
    }, 300);
}

function clearSearch() {
    document.getElementById('search-description').value = '';
    performAdvancedSearch();
}

function applyAdvancedFilters() {
    performAdvancedSearch();
}

function clearAllFilters() {
    // Clear all filter inputs
    document.getElementById('search-description').value = '';
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value = '';
    document.getElementById('filter-store').value = '';
    document.getElementById('filter-amount-range').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('search-exact-match').checked = false;
    document.getElementById('search-case-sensitive').checked = false;
    document.getElementById('include-auto-saved').checked = false;
    document.getElementById('search-in-lines').checked = true;
    
    // Reset quick filter buttons
    document.querySelectorAll('.filter-quick-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Hide search summary
    document.getElementById('search-summary').style.display = 'none';
    
    // Perform fresh search
    loadRecentEntriesList();
}

function performAdvancedSearch() {
    const searchParams = collectSearchParameters();
    
    // Update search filters state
    currentSearchFilters = searchParams;
    
    // Update modal status
    updateModalStatus('Searching...');
    
    // Build query string
    const queryParams = new URLSearchParams(searchParams);
    
    const tbody = document.getElementById('recent-entries-tbody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Searching...</span>
                </div>
                <div class="mt-2">Searching entries...</div>
            </td>
        </tr>
    `;
    
    fetch(`get_recent_entries.php?action=search&${queryParams.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAdvancedSearchResults(data.entries, data.search_info);
            } else {
                showRecentEntriesError('Search failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error performing advanced search:', error);
            showRecentEntriesError('Network error occurred during search.');
        })
        .finally(() => {
            updateModalStatus('Ready');
        });
}

function collectSearchParameters() {
    const params = {
        search: document.getElementById('search-description').value.trim(),
        date_from: document.getElementById('filter-date-from').value,
        date_to: document.getElementById('filter-date-to').value,
        store_id: document.getElementById('filter-store').value,
        amount_range: document.getElementById('filter-amount-range').value,
        status: document.getElementById('filter-status').value,
        exact_match: document.getElementById('search-exact-match').checked,
        case_sensitive: document.getElementById('search-case-sensitive').checked,
        include_auto_saved: document.getElementById('include-auto-saved').checked,
        search_in_lines: document.getElementById('search-in-lines').checked,
        sort_by: currentSortBy,
        sort_order: currentSortOrder,
        limit: 20,
        offset: 0
    };
    
    // Remove empty parameters
    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === false) {
            delete params[key];
        }
    });
    
    return params;
}

function displayAdvancedSearchResults(entries, searchInfo) {
    displayRecentEntriesList(entries);
    
    // Update search summary
    updateSearchSummary(searchInfo);
    
    // Update pagination info
    updatePaginationInfo(searchInfo.pagination);
}

function updateSearchSummary(searchInfo) {
    const summaryElement = document.getElementById('search-summary');
    const resultsCountElement = document.getElementById('search-results-count');
    
    if (searchInfo.total_found > 0 || searchInfo.search_text || hasActiveFilters(searchInfo.filters_applied)) {
        summaryElement.style.display = 'block';
        
        let summaryText = `${searchInfo.total_found} entries found`;
        
        if (searchInfo.search_text) {
            summaryText += ` for "${searchInfo.search_text}"`;
        }
        
        if (hasActiveFilters(searchInfo.filters_applied)) {
            summaryText += ' (filtered)';
        }
        
        resultsCountElement.textContent = summaryText;
    } else {
        summaryElement.style.display = 'none';
    }
}

function hasActiveFilters(filtersApplied) {
    return Object.values(filtersApplied).some(filter => filter === true);
}

function updatePaginationInfo(pagination) {
    const paginationInfo = document.getElementById('pagination-showing');
    
    if (pagination.count > 0) {
        paginationInfo.textContent = `Showing ${pagination.count} entries`;
    } else {
        paginationInfo.textContent = 'No entries found';
    }
}

function updateModalStatus(status) {
    const statusElement = document.getElementById('modal-status-text');
    if (statusElement) {
        statusElement.textContent = status;
    }
}

// Quick filter functions
function filterByToday() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('filter-date-from').value = today;
    document.getElementById('filter-date-to').value = today;
    
    // Update button state
    updateQuickFilterButtons('today');
    
    performAdvancedSearch();
}

function filterByHighAmount() {
    document.getElementById('filter-amount-range').value = '5000000+';
    
    // Update button state
    updateQuickFilterButtons('high-amount');
    
    performAdvancedSearch();
}

function updateQuickFilterButtons(activeFilter) {
    document.querySelectorAll('.filter-quick-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    if (activeFilter) {
        document.querySelector(`[data-filter="${activeFilter}"]`)?.classList.add('active');
    }
}

// Enhanced Recent Entries function with advanced search integration
function showRecentEntriesModal() {
    // Reset search state
    currentSearchFilters = {};
    
    // Load initial data
    loadRecentEntriesList();
    
    const modal = new bootstrap.Modal(document.getElementById('recentEntriesModal'));
    modal.show();
}

// Sorting functions
function sortBy(column) {
    if (currentSortBy === column) {
        // Toggle sort order
        currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
    } else {
        // New column, default to descending
        currentSortBy = column;
        currentSortOrder = 'desc';
    }
    
    // Update sort indicators
    updateSortIndicators(column, currentSortOrder);
    
    // Perform search/filter with new sort
    if (Object.keys(currentSearchFilters).length > 0) {
        performAdvancedSearch();
    } else {
        loadRecentEntriesList();
    }
}

function updateSortIndicators(activeColumn, sortOrder) {
    // Remove existing indicators
    document.querySelectorAll('[onclick^="sortBy"]').forEach(btn => {
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = 'bi bi-arrow-down-up small';
        }
    });
    
    // Add indicator to active column
    const activeButton = document.querySelector(`[onclick="sortBy('${activeColumn}')"]`);
    if (activeButton) {
        const icon = activeButton.querySelector('i');
        if (icon) {
            icon.className = sortOrder === 'asc' ? 'bi bi-arrow-up small' : 'bi bi-arrow-down small';
        }
    }
}

// Enhanced filter functions
function filterRecentEntries(days) {
    // Clear existing date filters
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value = '';
    
    // Set date range based on days
    const toDate = new Date();
    const fromDate = new Date();
    fromDate.setDate(toDate.getDate() - parseInt(days));
    
    document.getElementById('filter-date-from').value = fromDate.toISOString().split('T')[0];
    document.getElementById('filter-date-to').value = toDate.toISOString().split('T')[0];
    
    // Update button states
    updateQuickFilterButtons(days);
    
    // Perform search
    performAdvancedSearch();
}

// Export and save filter functions
function exportSearchResults() {
    const searchParams = collectSearchParameters();
    
    // Add export flag
    searchParams.export = 'csv';
    
    // Create download link
    const queryParams = new URLSearchParams(searchParams);
    const downloadUrl = `get_recent_entries.php?action=search&${queryParams.toString()}`;
    
    // Trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `journal_entries_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showSuccess('Export initiated. Download should start shortly.');
}

function saveSearchAsFilter() {
    const filterName = prompt('Enter a name for this filter:');
    if (!filterName || filterName.trim() === '') {
        return;
    }
    
    const searchParams = collectSearchParameters();
    
    // Save to localStorage for now (in production, this would be saved to the server)
    const savedFilters = JSON.parse(localStorage.getItem('journal_search_filters') || '{}');
    savedFilters[filterName.trim()] = {
        name: filterName.trim(),
        filters: searchParams,
        created_at: new Date().toISOString()
    };
    
    localStorage.setItem('journal_search_filters', JSON.stringify(savedFilters));
    
    showSuccess(`Filter "${filterName.trim()}" saved successfully!`);
}

// Load saved filters (for future implementation)
function loadSavedFilters() {
    const savedFilters = JSON.parse(localStorage.getItem('journal_search_filters') || '{}');
    
    // This could be implemented as a dropdown or separate modal
    console.log('Saved filters:', savedFilters);
    
    return savedFilters;
}

// Initialize advanced search when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for real-time search
    const searchInput = document.getElementById('search-description');
    if (searchInput) {
        searchInput.addEventListener('input', applyRealTimeSearch);
    }
    
    // Add event listeners for filter changes
    const filterElements = [
        'filter-date-from', 'filter-date-to', 'filter-store', 
        'filter-amount-range', 'filter-status'
    ];
    
    filterElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', () => {
                // Debounce filter changes
                clearTimeout(window.filterTimeout);
                window.filterTimeout = setTimeout(performAdvancedSearch, 500);
            });
        }
    });
    
    // Add event listeners for checkbox filters
    const checkboxElements = [
        'search-exact-match', 'search-case-sensitive', 
        'include-auto-saved', 'search-in-lines'
    ];
    
    checkboxElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', performAdvancedSearch);
        }
    });
    
    // Real-time validation is initialized in real_time_validation.js
    console.log('Advanced search initialized');
});
