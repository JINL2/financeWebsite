// Enhanced Pagination System for Transactions Page
// Provides numbered pagination similar to Korean sites

// Global pagination variables
let currentPage = 1;
let totalPages = 1;
let totalEntries = 0;
let currentLimit = 20;

// Enhanced pagination system with numbered pages
function updatePagination(pagination) {
    currentPage = pagination.page;
    totalPages = pagination.totalPages;
    totalEntries = pagination.total;
    
    // Update results count
    const startEntry = ((currentPage - 1) * currentLimit) + 1;
    const endEntry = Math.min(currentPage * currentLimit, totalEntries);
    document.getElementById('results-count').textContent = 
        `Showing ${startEntry}-${endEntry} of ${totalEntries} entries`;
    
    // Generate and update pagination HTML
    const paginationContainer = document.getElementById('pagination-container');
    if (!paginationContainer) return;
    
    paginationContainer.innerHTML = generatePaginationHTML();
    
    // Show pagination if more than one page
    if (totalPages > 1) {
        paginationContainer.style.display = 'flex';
    } else {
        paginationContainer.style.display = 'none';
    }
}

// Generate pagination HTML with numbered pages
function generatePaginationHTML() {
    if (totalPages <= 1) return '';
    
    let html = `
        <div class="pagination-info">
            <span class="page-indicator">${currentPage}/${totalPages}</span>
            <span id="total-entries">Total: ${totalEntries} entries</span>
        </div>
        <div class="pagination-controls-new">
    `;
    
    // Previous button
    if (currentPage > 1) {
        html += `<button class="page-btn-new" onclick="goToPage(${currentPage - 1})" title="Previous Page">&laquo; Previous</button>`;
    }
    
    // Calculate page numbers to show (max 10 pages)
    const maxVisiblePages = 10;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    // Adjust start page if we don't have enough pages at the end
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // Generate page number buttons
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === currentPage;
        html += `
            <button class="page-btn-new ${isActive ? 'active' : ''}" 
                    onclick="goToPage(${i})" 
                    ${isActive ? 'disabled' : ''}>
                ${i}
            </button>
        `;
    }
    
    // Show ellipsis and last page if needed
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<span class="page-ellipsis">...</span>`;
        }
        // Show last page
        if (endPage < totalPages) {
            html += `
                <button class="page-btn-new" onclick="goToPage(${totalPages})">
                    ${totalPages}
                </button>
            `;
        }
    }
    
    // Next button
    if (currentPage < totalPages) {
        html += `<button class="page-btn-new" onclick="goToPage(${currentPage + 1})" title="Next Page">Next &raquo;</button>`;
    }
    
    html += '</div>';
    return html;
}

// Go to specific page
function goToPage(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    loadTransactions(page);
}

// Initialize pagination styles
function initPaginationStyles() {
    // Check if styles already exist
    if (document.getElementById('enhanced-pagination-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'enhanced-pagination-styles';
    style.textContent = `
        /* Enhanced Pagination Controls */
        .pagination-controls-new {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: wrap;
        }

        .page-btn-new {
            background: white;
            border: 2px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            min-width: 40px;
            justify-content: center;
            font-size: 0.875rem;
        }

        .page-btn-new:hover:not(:disabled):not(.active) {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }

        .page-btn-new:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .page-btn-new.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
            cursor: default;
        }

        .page-ellipsis {
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.5rem 0.25rem;
            display: flex;
            align-items: center;
        }

        /* Hide original pagination controls */
        .pagination-controls {
            display: none !important;
        }

        /* Responsive pagination */
        @media (max-width: 768px) {
            .pagination-controls-new {
                justify-content: center;
                gap: 0.125rem;
            }
            
            .page-btn-new {
                padding: 0.375rem 0.5rem;
                min-width: 36px;
                font-size: 0.8rem;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 0.75rem;
            }
        }
    `;
    
    document.head.appendChild(style);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPaginationStyles);
} else {
    initPaginationStyles();
}
