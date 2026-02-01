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

// Sort meal buttons by shade on load (darker shades first)
const mealContainer = document.querySelector('.grid.grid-cols-2.md\\:grid-cols-3.gap-3');
if (mealContainer) {
  // Select the .relative wrapper divs that contain the forms
  const wrappers = Array.from(mealContainer.querySelectorAll('.relative'));
  wrappers.sort((a, b) => {
    const buttonA = a.querySelector('.meal-form button');
    const buttonB = b.querySelector('.meal-form button');

    if (!buttonA || !buttonB) return 0;

    // Extract color class from class list
    const colorA = Array.from(buttonA.classList).find(cls =>
      (cls.startsWith('bg-orange-') || cls.startsWith('bg-amber-') || cls.startsWith('bg-yellow-')) && !cls.includes('hover'));
    const colorB = Array.from(buttonB.classList).find(cls =>
      (cls.startsWith('bg-orange-') || cls.startsWith('bg-amber-') || cls.startsWith('bg-yellow-')) && !cls.includes('hover'));

    // Define color order (darker shades first)
    const colorOrder = {
      'bg-orange-500': 1,
      'bg-amber-500': 2,
      'bg-yellow-500': 3
    };

    return (colorOrder[colorA] || 5) - (colorOrder[colorB] || 5);
  });

  wrappers.forEach(wrapper => {
    mealContainer.appendChild(wrapper);
  });
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', () => {
  makeTablesSortable();
});
