document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM fully loaded and parsed');

    // --- Filtre pour Materials ---
    const materialNameSearchInput = document.getElementById('materialNameSearch');
    const materialsTable = document.getElementById('materialsTable');

    if (materialNameSearchInput && materialsTable) {
        console.log('Material search input and table found.');
        materialNameSearchInput.addEventListener('keyup', function () {
            const searchTerm = materialNameSearchInput.value.toLowerCase();
            const tbody = materialsTable.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            const rows = tbody.getElementsByTagName('tr');
            for (let i = 0; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[1]; // Colonne Name
                if (nameCell) {
                    const nameText = (nameCell.textContent || nameCell.innerText || "").trim();
                    rows[i].style.display = nameText.toLowerCase().indexOf(searchTerm) > -1 ? '' : 'none';
                }
            }
        });
    } else {
        if (!materialNameSearchInput) console.warn('Search input #materialNameSearch not found!');
        if (!materialsTable) console.warn('Table #materialsTable not found!');
    }

    // --- Filtre pour Brands ---
    const brandNameSearch = document.getElementById('brandNameSearch');
    const brandAbbreviationSearch = document.getElementById('brandAbbreviationSearch');
    const brandsTable = document.getElementById('brandsTable');

    function filterBrandsTable() {
        if (!brandsTable) return;
        const nameTerm = brandNameSearch ? brandNameSearch.value.toLowerCase() : '';
        const abbrTerm = brandAbbreviationSearch ? brandAbbreviationSearch.value.toLowerCase() : '';
        const tbody = brandsTable.getElementsByTagName('tbody')[0];
        if (!tbody) return;
        const rows = tbody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const nameCell = rows[i].getElementsByTagName('td')[1]; // Colonne Name pour Brands
            const abbrCell = rows[i].getElementsByTagName('td')[2]; // Colonne Abbreviation pour Brands
            let nameMatch = true;
            let abbrMatch = true;

            if (nameTerm && nameCell) {
                nameMatch = (nameCell.textContent || nameCell.innerText || "").trim().toLowerCase().indexOf(nameTerm) > -1;
            }
            if (abbrTerm && abbrCell) {
                abbrMatch = (abbrCell.textContent || abbrCell.innerText || "").trim().toLowerCase().indexOf(abbrTerm) > -1;
            }
            rows[i].style.display = (nameMatch && abbrMatch) ? '' : 'none';
        }
    }

    if (brandNameSearch && brandsTable) {
        console.log('Brand name search and table found.');
        brandNameSearch.addEventListener('keyup', filterBrandsTable);
    } else {
        if (!brandNameSearch) console.warn('Search input #brandNameSearch not found!');
        if (!brandsTable && brandNameSearch) console.warn('Table #brandsTable not found (needed by brandNameSearch)!');
    }
    if (brandAbbreviationSearch && brandsTable) {
        console.log('Brand abbreviation search and table found.');
        brandAbbreviationSearch.addEventListener('keyup', filterBrandsTable);
    } else {
        if (!brandAbbreviationSearch) console.warn('Search input #brandAbbreviationSearch not found!');
         if (!brandsTable && brandAbbreviationSearch) console.warn('Table #brandsTable not found (needed by brandAbbreviationSearch)!');
    }


    // --- Filtre pour Colors ---
    const colorNameSearch = document.getElementById('colorNameSearch');
    const colorHexSearch = document.getElementById('colorHexSearch');
    const colorBaseCategorySearch = document.getElementById('colorBaseCategorySearch');
    const colorsTable = document.getElementById('colorsTable');

    function filterColorsTable() {
        if (!colorsTable) return;
        const nameTerm = colorNameSearch ? colorNameSearch.value.toLowerCase() : '';
        const hexTerm = colorHexSearch ? colorHexSearch.value.toLowerCase() : '';
        const catTerm = colorBaseCategorySearch ? colorBaseCategorySearch.value.toLowerCase() : '';
        const tbody = colorsTable.getElementsByTagName('tbody')[0];
        if (!tbody) return;
        const rows = tbody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            // Attention aux index des colonnes, l'image est en [1]
            const nameCell = rows[i].getElementsByTagName('td')[2]; // Name est en 3ème position (index 2)
            const hexCell = rows[i].getElementsByTagName('td')[3];  // Hex est en 4ème position (index 3)
            const catCell = rows[i].getElementsByTagName('td')[4];  // Base Category est en 5ème position (index 4)
            
            let nameMatch = true;
            let hexMatch = true;
            let catMatch = true;

            if (nameTerm && nameCell) {
                nameMatch = (nameCell.textContent || nameCell.innerText || "").trim().toLowerCase().indexOf(nameTerm) > -1;
            }
            if (hexTerm && hexCell) {
                hexMatch = (hexCell.textContent || hexCell.innerText || "").trim().toLowerCase().indexOf(hexTerm) > -1;
            }
            if (catTerm && catCell) {
                catMatch = (catCell.textContent || catCell.innerText || "").trim().toLowerCase().indexOf(catTerm) > -1;
            }
            rows[i].style.display = (nameMatch && hexMatch && catMatch) ? '' : 'none';
        }
    }
    if (colorsTable) { // Un seul check pour la table, puis pour chaque input
        if (colorNameSearch) {
            console.log('Color name search and table found.');
            colorNameSearch.addEventListener('keyup', filterColorsTable);
        } else console.warn('Search input #colorNameSearch not found!');
        
        if (colorHexSearch) {
            console.log('Color hex search and table found.');
            colorHexSearch.addEventListener('keyup', filterColorsTable);
        } else console.warn('Search input #colorHexSearch not found!');

        if (colorBaseCategorySearch) {
            console.log('Color base category search and table found.');
            colorBaseCategorySearch.addEventListener('keyup', filterColorsTable);
        } else console.warn('Search input #colorBaseCategorySearch not found!');
    } else {
        console.warn('Table #colorsTable not found!');
    }

});

    // Si vous voulez ajouter des filtres pour d'autres colonnes, dupliquez la logique
    // ou créez une fonction de filtrage plus générique.
    // Par exemple, pour filtrer sur plusieurs colonnes :
    // Vous pourriez avoir un champ de recherche global ou des champs par colonne.
    // Le principe reste de lire les valeurs des cellules et de comparer.
