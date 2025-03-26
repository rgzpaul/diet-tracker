function makeTablesSortable(tableSelector = 'table') {
    const tables = document.querySelectorAll(tableSelector);
    
    tables.forEach(table => {
      const headers = table.querySelectorAll('thead th');
      
      headers.forEach((header, index) => {
        // Add sorting cursor
        header.style.cursor = 'pointer';
        
        // Track sort direction
        header.setAttribute('data-sort-direction', 'none');
        
        header.addEventListener('click', () => {
          // Get current sort direction
          const currentDirection = header.getAttribute('data-sort-direction');
          
          // Reset all headers
          headers.forEach(h => {
            h.setAttribute('data-sort-direction', 'none');
          });
          
          // Set new sort direction
          let nextDirection = (currentDirection === 'none' || currentDirection === 'desc') ? 'asc' : 'desc';
          header.setAttribute('data-sort-direction', nextDirection);
          
          // Get the tbody and rows
          const tbody = table.querySelector('tbody');
          const rows = Array.from(tbody.querySelectorAll('tr'));
          
          // Sort rows
          rows.sort((rowA, rowB) => {
            const cellA = rowA.querySelectorAll('td')[index];
            const cellB = rowB.querySelectorAll('td')[index];
            
            if (!cellA || !cellB) return 0;
            
            let valueA = cellA.innerText.trim();
            let valueB = cellB.innerText.trim();
            
            // Check if values are numbers
            const numA = parseFloat(valueA);
            const numB = parseFloat(valueB);
            
            if (!isNaN(numA) && !isNaN(numB)) {
              return nextDirection === 'asc' ? numA - numB : numB - numA;
            } else {
              return nextDirection === 'asc' 
                ? valueA.localeCompare(valueB) 
                : valueB.localeCompare(valueA);
            }
          });
          
          // Reappend rows in new order
          rows.forEach(row => {
            tbody.appendChild(row);
          });
        });
      });
    });
  }
  
  // Initialize on document ready
  document.addEventListener('DOMContentLoaded', () => {
    makeTablesSortable();
  });