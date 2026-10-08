/**
 * ============================================
 * MASTER RETRIBUSI JAVASCRIPT
 * ============================================
 * 
 * Handles interactions for Master Retribusi page
 * 
 * Features:
 * - Search/filter table
 * - Delete confirmation
 * - Real-time table filtering
 */

(function() {
    'use strict';

    /**
     * Initialize when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initSearch();
        initDeleteButtons();
    });

    /**
     * Search/Filter Table
     * Filters table rows based on search input
     */
    function initSearch() {
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('tableBody');

        if (!searchInput || !tableBody) return;

        // Debounce search for better performance
        const debouncedSearch = debounce(function(searchTerm) {
            const rows = tableBody.querySelectorAll('tr');
            const term = searchTerm.toLowerCase();

            rows.forEach(function(row) {
                const searchData = row.getAttribute('data-search') || '';
                const shouldShow = searchData.includes(term);
                
                row.style.display = shouldShow ? '' : 'none';
            });

            // Show "no results" message if all rows are hidden
            const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
            showNoResultsMessage(visibleRows.length === 0);
        }, 300);

        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value);
        });
    }

    /**
     * Show/Hide No Results Message
     * @param {boolean} show - Whether to show the message
     */
    function showNoResultsMessage(show) {
        const tableBody = document.getElementById('tableBody');
        let noResultsRow = document.getElementById('noResultsRow');

        if (show) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsRow';
                noResultsRow.innerHTML = `
                    <td colspan="4" class="text-center" style="padding: 2rem;">
                        <div class="empty-icon" style="font-size: 3rem; opacity: 0.5;">🔍</div>
                        <p style="color: var(--gray-600); margin-top: 1rem;">
                            Tidak ada data yang sesuai dengan pencarian
                        </p>
                    </td>
                `;
                tableBody.appendChild(noResultsRow);
            }
        } else {
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    }

    /**
     * Initialize Delete Buttons
     * Adds confirmation dialog before deletion
     */
    function initDeleteButtons() {
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const itemName = this.getAttribute('data-name');
                const form = this.closest('.delete-form');

                if (!form) return;

                // Show confirmation dialog
                confirmDelete(
                    `Apakah Anda yakin ingin menghapus retribusi "<strong>${itemName}</strong>"?<br><br>Data yang sudah dihapus tidak dapat dikembalikan.`,
                    function() {
                        // On confirm - submit form
                        form.submit();
                        showNotification('Menghapus data...', 'info');
                    },
                    function() {
                        // On cancel
                        showNotification('Penghapusan dibatalkan', 'info');
                    }
                );
            });
        });
    }

})();
