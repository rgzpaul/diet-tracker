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

// Sort meal buttons by color on load
const mealContainer = document.querySelector('.grid.grid-cols-2.md\\:grid-cols-3.gap-4');
if (mealContainer) {
  const forms = Array.from(mealContainer.querySelectorAll('.meal-form'));
  forms.sort((a, b) => {
    const buttonA = a.querySelector('button');
    const buttonB = b.querySelector('button');

    // Extract color class from class list
    const colorA = Array.from(buttonA.classList).find(cls =>
      cls.startsWith('bg-') && !cls.includes('hover'));
    const colorB = Array.from(buttonB.classList).find(cls =>
      cls.startsWith('bg-') && !cls.includes('hover'));

    // Define color order
    const colorOrder = {
      'bg-green-800': 1,
      'bg-blue-600': 2,
      'bg-amber-800': 3,
      'bg-orange-600': 4
    };

    return colorOrder[colorA] - colorOrder[colorB];
  });

  forms.forEach(form => {
    mealContainer.appendChild(form);
  });
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', () => {
  makeTablesSortable();
});