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

    // --- Filtre pour CategoryTypes ---
    const ctNameSearch = document.getElementById('ctNameSearch');
    const ctCategorySearch = document.getElementById('ctCategorySearch');
    const ctCodeSearch = document.getElementById('ctCodeSearch');
    const categoryTypesTable = document.getElementById('categoryTypesTable');

    function filterCategoryTypesTable() {
        if (!categoryTypesTable) return;
        const nameTerm = ctNameSearch ? ctNameSearch.value.toLowerCase() : '';
        const catTerm = ctCategorySearch ? ctCategorySearch.value.toLowerCase() : '';
        const codeTerm = ctCodeSearch ? ctCodeSearch.value.toLowerCase() : '';
        const tbody = categoryTypesTable.getElementsByTagName('tbody')[0];
        if (!tbody) return;
        const rows = tbody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const nameCell = rows[i].getElementsByTagName('td')[1]; // Name
            const catCell = rows[i].getElementsByTagName('td')[2];  // Category
            const codeCell = rows[i].getElementsByTagName('td')[3]; // Code
            
            let nameMatch = true;
            let catMatch = true;
            let codeMatch = true;

            if (nameTerm && nameCell) {
                nameMatch = (nameCell.textContent || nameCell.innerText || "").trim().toLowerCase().indexOf(nameTerm) > -1;
            }
            if (catTerm && catCell) {
                catMatch = (catCell.textContent || catCell.innerText || "").trim().toLowerCase().indexOf(catTerm) > -1;
            }
            if (codeTerm && codeCell) {
                codeMatch = (codeCell.textContent || codeCell.innerText || "").trim().toLowerCase().indexOf(codeTerm) > -1;
            }
            rows[i].style.display = (nameMatch && catMatch && codeMatch) ? '' : 'none';
        }
    }
    if (categoryTypesTable) {
        if (ctNameSearch) ctNameSearch.addEventListener('keyup', filterCategoryTypesTable);
        else console.warn('#ctNameSearch not found');
        if (ctCategorySearch) ctCategorySearch.addEventListener('keyup', filterCategoryTypesTable);
        else console.warn('#ctCategorySearch not found');
        if (ctCodeSearch) ctCodeSearch.addEventListener('keyup', filterCategoryTypesTable);
        else console.warn('#ctCodeSearch not found');
    } else {
        console.warn('#categoryTypesTable not found');
    }

    // --- Filtre pour EventTypes ---
    const etNameSearch = document.getElementById('etNameSearch');
    const etDescriptionSearch = document.getElementById('etDescriptionSearch');
    // Le filtre sur day_moments_names est plus complexe car c'est une chaîne concaténée
    // Pour l'instant, on filtre sur nom et description.
    const eventTypesTable = document.getElementById('eventTypesTable');

    function filterEventTypesTable() {
        if (!eventTypesTable) return;
        const nameTerm = etNameSearch ? etNameSearch.value.toLowerCase() : '';
        const descTerm = etDescriptionSearch ? etDescriptionSearch.value.toLowerCase() : '';
        const tbody = eventTypesTable.getElementsByTagName('tbody')[0];
        if (!tbody) return;
        const rows = tbody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const nameCell = rows[i].getElementsByTagName('td')[1]; // Name
            const descCell = rows[i].getElementsByTagName('td')[2];  // Description
            // const momentsCell = rows[i].getElementsByTagName('td')[3]; // Day Moments (plus complexe à filtrer)
            
            let nameMatch = true;
            let descMatch = true;

            if (nameTerm && nameCell) {
                nameMatch = (nameCell.textContent || nameCell.innerText || "").trim().toLowerCase().indexOf(nameTerm) > -1;
            }
            if (descTerm && descCell) {
                descMatch = (descCell.textContent || descCell.innerText || "").trim().toLowerCase().indexOf(descTerm) > -1;
            }
            rows[i].style.display = (nameMatch && descMatch) ? '' : 'none';
        }
    }
    if (eventTypesTable) {
        if (etNameSearch) etNameSearch.addEventListener('keyup', filterEventTypesTable);
        else console.warn('#etNameSearch not found');
        if (etDescriptionSearch) etDescriptionSearch.addEventListener('keyup', filterEventTypesTable);
        else console.warn('#etDescriptionSearch not found');
    } else {
        console.warn('#eventTypesTable not found');
    }

    // --- Filtre pour ItemUsers ---
    const iuNameSearch = document.getElementById('iuNameSearch');
    const iuAbbreviationSearch = document.getElementById('iuAbbreviationSearch');
    const itemUsersTable = document.getElementById('itemUsersTable');

    function filterItemUsersTable() {
        if (!itemUsersTable) return;
        const nameTerm = iuNameSearch ? iuNameSearch.value.toLowerCase() : '';
        const abbrTerm = iuAbbreviationSearch ? iuAbbreviationSearch.value.toLowerCase() : '';
        const tbody = itemUsersTable.getElementsByTagName('tbody')[0];
        if (!tbody) return;
        const rows = tbody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const nameCell = rows[i].getElementsByTagName('td')[1]; // Name
            const abbrCell = rows[i].getElementsByTagName('td')[2];  // Abbreviation
            
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
    if (itemUsersTable) {
        if (iuNameSearch) iuNameSearch.addEventListener('keyup', filterItemUsersTable);
        else console.warn('#iuNameSearch not found for itemUsersTable');
        if (iuAbbreviationSearch) iuAbbreviationSearch.addEventListener('keyup', filterItemUsersTable);
        else console.warn('#iuAbbreviationSearch not found for itemUsersTable');
    } else {
        // Log only if at least one search input for this table was found
        if (iuNameSearch || iuAbbreviationSearch) console.warn('#itemUsersTable not found');
    }

    // --- Filtre pour Suppliers ---
    const supNameSearch = document.getElementById('supNameSearch');
    const supContactSearch = document.getElementById('supContactSearch');
    const supEmailSearch = document.getElementById('supEmailSearch');
    const supPhoneSearch = document.getElementById('supPhoneSearch');
    const suppliersTable = document.getElementById('suppliersTable');

    function filterSuppliersTable() {
        if (!suppliersTable) return;
        const nameTerm = supNameSearch ? supNameSearch.value.toLowerCase() : '';
        const contactTerm = supContactSearch ? supContactSearch.value.toLowerCase() : '';
        const emailTerm = supEmailSearch ? supEmailSearch.value.toLowerCase() : '';
        const phoneTerm = supPhoneSearch ? supPhoneSearch.value.toLowerCase() : '';
        const tbody = suppliersTable.getElementsByTagName('tbody')[0];
        if (!tbody) return;
        const rows = tbody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const nameCell = rows[i].getElementsByTagName('td')[1];     // Name
            const contactCell = rows[i].getElementsByTagName('td')[2];  // Contact
            const emailCell = rows[i].getElementsByTagName('td')[3];    // Email
            const phoneCell = rows[i].getElementsByTagName('td')[4];    // Phone
            
            let nameMatch = true, contactMatch = true, emailMatch = true, phoneMatch = true;

            if (nameTerm && nameCell) nameMatch = (nameCell.textContent || nameCell.innerText || "").trim().toLowerCase().indexOf(nameTerm) > -1;
            if (contactTerm && contactCell) contactMatch = (contactCell.textContent || contactCell.innerText || "").trim().toLowerCase().indexOf(contactTerm) > -1;
            if (emailTerm && emailCell) emailMatch = (emailCell.textContent || emailCell.innerText || "").trim().toLowerCase().indexOf(emailTerm) > -1;
            if (phoneTerm && phoneCell) phoneMatch = (phoneCell.textContent || phoneCell.innerText || "").trim().toLowerCase().indexOf(phoneTerm) > -1;
            
            rows[i].style.display = (nameMatch && contactMatch && emailMatch && phoneMatch) ? '' : 'none';
        }
    }
    if (suppliersTable) {
        if (supNameSearch) supNameSearch.addEventListener('keyup', filterSuppliersTable);
        else console.warn('#supNameSearch not found for suppliersTable');
        if (supContactSearch) supContactSearch.addEventListener('keyup', filterSuppliersTable);
        else console.warn('#supContactSearch not found for suppliersTable');
        if (supEmailSearch) supEmailSearch.addEventListener('keyup', filterSuppliersTable);
        else console.warn('#supEmailSearch not found for suppliersTable');
        if (supPhoneSearch) supPhoneSearch.addEventListener('keyup', filterSuppliersTable);
        else console.warn('#supPhoneSearch not found for suppliersTable');
    } else {
        if (supNameSearch || supContactSearch || supEmailSearch || supPhoneSearch) console.warn('#suppliersTable not found');
    }

    // --- Filtre pour StorageLocations ---
    const slRoomSearch = document.getElementById('slRoomSearch');
    const slAreaSearch = document.getElementById('slAreaSearch');
    const slShelfSearch = document.getElementById('slShelfSearch');
    const slLevelSearch = document.getElementById('slLevelSearch');
    const slSpotSearch = document.getElementById('slSpotSearch');
    const slFullPathSearch = document.getElementById('slFullPathSearch'); // Nouveau
    const storageLocationsTable = document.getElementById('storageLocationsTable');

    function filterStorageLocationsTable() {
        if (!storageLocationsTable) return;
        const roomTerm = slRoomSearch ? slRoomSearch.value.toLowerCase() : '';
        const areaTerm = slAreaSearch ? slAreaSearch.value.toLowerCase() : '';
        const shelfTerm = slShelfSearch ? slShelfSearch.value.toLowerCase() : '';
        const levelTerm = slLevelSearch ? slLevelSearch.value.toLowerCase() : '';
        const spotTerm = slSpotSearch ? slSpotSearch.value.toLowerCase() : '';
        const fullPathTerm = slFullPathSearch ? slFullPathSearch.value.toLowerCase() : ''; // Nouveau

        const tbody = storageLocationsTable.getElementsByTagName('tbody')[0];
        if (!tbody) return;
        const rows = tbody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const roomCell = rows[i].getElementsByTagName('td')[1];
            const areaCell = rows[i].getElementsByTagName('td')[2];
            const shelfCell = rows[i].getElementsByTagName('td')[3];
            const levelCell = rows[i].getElementsByTagName('td')[4]; // Correspond à Level/Sec.
            const spotCell = rows[i].getElementsByTagName('td')[5];  // Correspond à Spot/Box
            const fullPathCell = rows[i].getElementsByTagName('td')[6]; // Correspond à Full Path

            let match = true; // Commence par true, et devient false si un filtre ne correspond pas

            if (roomTerm && roomCell) {
                if ((roomCell.textContent || roomCell.innerText || "").trim().toLowerCase().indexOf(roomTerm) === -1) match = false;
            }
            if (match && areaTerm && areaCell) { // Si déjà false, pas besoin de vérifier plus
                if ((areaCell.textContent || areaCell.innerText || "").trim().toLowerCase().indexOf(areaTerm) === -1) match = false;
            }
            if (match && shelfTerm && shelfCell) {
                if ((shelfCell.textContent || shelfCell.innerText || "").trim().toLowerCase().indexOf(shelfTerm) === -1) match = false;
            }
            if (match && levelTerm && levelCell) {
                if ((levelCell.textContent || levelCell.innerText || "").trim().toLowerCase().indexOf(levelTerm) === -1) match = false;
            }
            if (match && spotTerm && spotCell) {
                if ((spotCell.textContent || spotCell.innerText || "").trim().toLowerCase().indexOf(spotTerm) === -1) match = false;
            }
            if (match && fullPathTerm && fullPathCell) {
                if ((fullPathCell.textContent || fullPathCell.innerText || "").trim().toLowerCase().indexOf(fullPathTerm) === -1) match = false;
            }
            
            rows[i].style.display = match ? '' : 'none';
        }
    }
    if (storageLocationsTable) {
        if (slRoomSearch) slRoomSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slAreaSearch) slAreaSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slShelfSearch) slShelfSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slLevelSearch) slLevelSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slSpotSearch) slSpotSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slFullPathSearch) slFullPathSearch.addEventListener('keyup', filterStorageLocationsTable); // Nouveau
    } else {
        if (slRoomSearch) console.warn('#storageLocationsTable not found but search fields exist');
    }

});

    // Si vous voulez ajouter des filtres pour d'autres colonnes, dupliquez la logique
    // ou créez une fonction de filtrage plus générique.
    // Par exemple, pour filtrer sur plusieurs colonnes :
    // Vous pourriez avoir un champ de recherche global ou des champs par colonne.
    // Le principe reste de lire les valeurs des cellules et de comparer.
