document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM fully loaded and parsed');

    // --- Initialisation pour la page des Articles ---
    const articlesTable = document.getElementById('articlesTable');
    if (articlesTable) {
        console.log('Articles page detected. Initializing article filters.');
        const articleNameSearch = document.getElementById('articleNameSearch'); // Ou 'filter_name_ref'
        const articleCategorySearch = document.getElementById('filter_category_id'); // Si c'est un input text, sinon pas de JS
        const articleBrandSearch = document.getElementById('filter_brand_id'); // Idem
        const articleStatusSearch = document.getElementById('filter_status_id'); // Idem
        const baseColorCategorySearch = document.getElementById('filter_base_color_category'); // Input text
        // Les filtres <select> sont gérés par soumission GET serveur, pas besoin de JS keyup ici.

        function filterArticlesTable() {
            const nameTerm = articleNameSearch ? articleNameSearch.value.toLowerCase() : '';
            const baseColorCatTerm = baseColorCategorySearch ? baseColorCategorySearch.value.toLowerCase() : '';
            // Ajoutez d'autres termes pour les inputs texte ici

            const tbody = articlesTable.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            const rows = tbody.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const refCell = rows[i].getElementsByTagName('td')[1];
                const nameCell = rows[i].getElementsByTagName('td')[2];
                const baseColorCatCell = rows[i].getElementsByTagName('td')[5]; // Ajustez l'index !
                
                let match = true;
                let nameRefContent = "";
                if (nameCell) nameRefContent += (nameCell.textContent || nameCell.innerText || "").trim().toLowerCase();
                if (refCell) nameRefContent += " " + (refCell.textContent || refCell.innerText || "").trim().toLowerCase();
                if (nameTerm && nameRefContent.indexOf(nameTerm) === -1) match = false;

                if (match && baseColorCatTerm && baseColorCatCell) {
                    if ((baseColorCatCell.textContent || baseColorCatCell.innerText || "").trim().toLowerCase().indexOf(baseColorCatTerm) === -1) match = false;
                }
                // Ajoutez d'autres conditions de match pour les inputs texte
                rows[i].style.display = match ? '' : 'none';
            }
        }

        if (articleNameSearch) articleNameSearch.addEventListener('keyup', filterArticlesTable);
        if (baseColorCategorySearch) baseColorCategorySearch.addEventListener('keyup', filterArticlesTable);
        // Attachez les listeners pour les autres inputs texte de filtre d'article

        // Logique pour Check All / Uncheck All Event Types (si ces boutons sont sur la page articles/index)
        const checkAllEventTypesBtn = document.getElementById('checkAllEventTypes');
        const uncheckAllEventTypesBtn = document.getElementById('uncheckAllEventTypes');
        const eventTypeFilterGroup = document.querySelector('.event-type-filter-group');

        if (checkAllEventTypesBtn && eventTypeFilterGroup) {
            checkAllEventTypesBtn.addEventListener('click', function() {
                eventTypeFilterGroup.querySelectorAll('.form-check-input').forEach(checkbox => checkbox.checked = true);
            });
        }
        if (uncheckAllEventTypesBtn && eventTypeFilterGroup) {
            uncheckAllEventTypesBtn.addEventListener('click', function() {
                eventTypeFilterGroup.querySelectorAll('.form-check-input').forEach(checkbox => checkbox.checked = false);
            });
        }
    }

    // --- Initialisation pour la page des Materials ---
    const materialsTable = document.getElementById('materialsTable');
    if (materialsTable) {
        console.log('Materials page detected.');
        const materialNameSearchInput = document.getElementById('materialNameSearch');
        if (materialNameSearchInput) {
            materialNameSearchInput.addEventListener('keyup', function () {
                const searchTerm = materialNameSearchInput.value.toLowerCase();
                const tbody = materialsTable.getElementsByTagName('tbody')[0];
                if (!tbody) return;
                const rows = tbody.getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    const nameCell = rows[i].getElementsByTagName('td')[1];
                    if (nameCell) {
                        rows[i].style.display = (nameCell.textContent || nameCell.innerText || "").trim().toLowerCase().indexOf(searchTerm) > -1 ? '' : 'none';
                    }
                }
            });
        } else console.warn('#materialNameSearch not found on materials page.');
    }

    // --- Initialisation pour la page des Brands ---
    const brandsTable = document.getElementById('brandsTable');
    if (brandsTable) {
        console.log('Brands page detected.');
        const brandNameSearch = document.getElementById('brandNameSearch');
        const brandAbbreviationSearch = document.getElementById('brandAbbreviationSearch');

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
		if (brandNameSearch) brandNameSearch.addEventListener('keyup', filterBrandsTable);
        else console.warn('#brandNameSearch not found on brands page.');
        if (brandAbbreviationSearch) brandAbbreviationSearch.addEventListener('keyup', filterBrandsTable);
        else console.warn('#brandAbbreviationSearch not found on brands page.');
    }


    // --- Initialisation pour la page des Colors ---
    const colorsTable = document.getElementById('colorsTable');
    if (colorsTable) {
        console.log('Colors page detected.');
        const colorNameSearch = document.getElementById('colorNameSearch');
        const colorHexSearch = document.getElementById('colorHexSearch');
        const colorBaseCategorySearchInput = document.getElementById('colorBaseCategorySearch'); // Distinguer de celui des articles

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
        if (colorNameSearch) colorNameSearch.addEventListener('keyup', filterColorsTable);
        else console.warn('#colorNameSearch not found on colors page.');
        // Ajouter des listeners pour colorHexSearch et colorBaseCategorySearchInput
        if (colorHexSearch) colorHexSearch.addEventListener('keyup', filterColorsTable);
        if (colorBaseCategorySearchInput) colorBaseCategorySearchInput.addEventListener('keyup', filterColorsTable);
    }

    // --- Initialisation pour CategoryTypes ---
    const categoryTypesTable = document.getElementById('categoryTypesTable');
    if (categoryTypesTable) {
        console.log('CategoryTypes page detected.');
        const ctNameSearch = document.getElementById('ctNameSearch');
		const ctCategorySearch = document.getElementById('ctCategorySearch');
		const ctCodeSearch = document.getElementById('ctCodeSearch');

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

        if (ctNameSearch) ctNameSearch.addEventListener('keyup', filterCategoryTypesTable);
        else console.warn('#ctNameSearch not found');
        if (ctCategorySearch) ctCategorySearch.addEventListener('keyup', filterCategoryTypesTable);
        else console.warn('#ctCategorySearch not found');
        if (ctCodeSearch) ctCodeSearch.addEventListener('keyup', filterCategoryTypesTable);
        else console.warn('#ctCodeSearch not found');
    } else {
        console.warn('#categoryTypesTable not found');
    }

    // --- Initialisation pour EventTypes ---
    const eventTypesTable = document.getElementById('eventTypesTable');
    if (eventTypesTable) {
        console.log('EventTypes page detected.');
        const etNameSearch = document.getElementById('etNameSearch');
		const etDescriptionSearch = document.getElementById('etDescriptionSearch');
		// Le filtre sur day_moments_names est plus complexe car c'est une chaîne concaténée
		// Pour l'instant, on filtre sur nom et description.

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

        if (etNameSearch) etNameSearch.addEventListener('keyup', filterEventTypesTable);
        else console.warn('#etNameSearch not found');
        if (etDescriptionSearch) etDescriptionSearch.addEventListener('keyup', filterEventTypesTable);
        else console.warn('#etDescriptionSearch not found');
    } else {
        console.warn('#eventTypesTable not found');
    }

    // --- Initialisation pour ItemUsers ---
    const itemUsersTable = document.getElementById('itemUsersTable');
    if (itemUsersTable) {
        console.log('ItemUsers page detected.');
        const iuNameSearch = document.getElementById('iuNameSearch');
		const iuAbbreviationSearch = document.getElementById('iuAbbreviationSearch');

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
        if (iuNameSearch) iuNameSearch.addEventListener('keyup', filterItemUsersTable);
        else console.warn('#iuNameSearch not found for itemUsersTable');
        if (iuAbbreviationSearch) iuAbbreviationSearch.addEventListener('keyup', filterItemUsersTable);
        else console.warn('#iuAbbreviationSearch not found for itemUsersTable');
    } else {
        // Log only if at least one search input for this table was found
        if (iuNameSearch || iuAbbreviationSearch) console.warn('#itemUsersTable not found');
    }

    // --- Initialisation pour Suppliers ---
    const suppliersTable = document.getElementById('suppliersTable');
    if (suppliersTable) {
        console.log('Suppliers page detected.');
		const supNameSearch = document.getElementById('supNameSearch');
		const supContactSearch = document.getElementById('supContactSearch');
		const supEmailSearch = document.getElementById('supEmailSearch');
		const supPhoneSearch = document.getElementById('supPhoneSearch');

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

    // --- Initialisation pour StorageLocations ---
    const storageLocationsTable = document.getElementById('storageLocationsTable');
    if (storageLocationsTable) {
        console.log('StorageLocations page detected.');
		const slRoomSearch = document.getElementById('slRoomSearch');
		const slAreaSearch = document.getElementById('slAreaSearch');
		const slShelfSearch = document.getElementById('slShelfSearch');
		const slLevelSearch = document.getElementById('slLevelSearch');
		const slSpotSearch = document.getElementById('slSpotSearch');
		const slFullPathSearch = document.getElementById('slFullPathSearch'); // Nouveau

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

        if (slRoomSearch) slRoomSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slAreaSearch) slAreaSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slShelfSearch) slShelfSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slLevelSearch) slLevelSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slSpotSearch) slSpotSearch.addEventListener('keyup', filterStorageLocationsTable);
        if (slFullPathSearch) slFullPathSearch.addEventListener('keyup', filterStorageLocationsTable); // Nouveau
    } else {
        if (slRoomSearch) console.warn('#storageLocationsTable not found but search fields exist');
    }


    // --- Initialisation pour Statuses ---
    const statusesTable = document.getElementById('statusesTable');
    if (statusesTable) {
        console.log('statusesTable page detected.');
		
		const statusNameSearch = document.getElementById('statusNameSearch');
		const statusAvailSearch = document.getElementById('statusAvailSearch');
		const statusDescSearch = document.getElementById('statusDescSearch');

		function filterStatusesTable() {
			if (!statusesTable) return;
			const nameTerm = statusNameSearch ? statusNameSearch.value.toLowerCase() : '';
			const availTerm = statusAvailSearch ? statusAvailSearch.value.toLowerCase().replace(' ', '_') : ''; // Gérer underscore pour enum
			const descTerm = statusDescSearch ? statusDescSearch.value.toLowerCase() : '';
			
			const tbody = statusesTable.getElementsByTagName('tbody')[0];
			if (!tbody) return;
			const rows = tbody.getElementsByTagName('tr');

			for (let i = 0; i < rows.length; i++) {
				const nameCell = rows[i].getElementsByTagName('td')[1];  // Name
				const availCell = rows[i].getElementsByTagName('td')[2]; // Availability
				const descCell = rows[i].getElementsByTagName('td')[3];  // Description
				
				let nameMatch = true, availMatch = true, descMatch = true;

				if (nameTerm && nameCell) nameMatch = (nameCell.textContent || nameCell.innerText || "").trim().toLowerCase().indexOf(nameTerm) > -1;
				if (availTerm && availCell) {
					// Pour la colonne availability, on compare avec la valeur brute (avec underscore)
					// ou on pourrait transformer le texte affiché en minuscule et remplacer ' ' par '_'
					let cellText = (availCell.textContent || availCell.innerText || "").trim().toLowerCase();
					// Si l'affichage est "In Stock", le texte de la cellule sera "in stock".
					// Si la recherche est "in_stock", on la transforme pour la comparaison.
					availMatch = cellText.indexOf(availTerm.replace('_', ' ')) > -1;
				}
				if (descTerm && descCell) descMatch = (descCell.textContent || descCell.innerText || "").trim().toLowerCase().indexOf(descTerm) > -1;
				
				rows[i].style.display = (nameMatch && availMatch && descMatch) ? '' : 'none';
			}
		}
		
        if (statusNameSearch) statusNameSearch.addEventListener('keyup', filterStatusesTable);
        else console.warn('#statusNameSearch not found');
        if (statusAvailSearch) statusAvailSearch.addEventListener('keyup', filterStatusesTable);
        else console.warn('#statusAvailSearch not found');
        if (statusDescSearch) statusDescSearch.addEventListener('keyup', filterStatusesTable);
        else console.warn('#statusDescSearch not found');
    } else {
        if (statusNameSearch) console.warn('#statusesTable not found but search fields exist');
    }

});

    // Si vous voulez ajouter des filtres pour d'autres colonnes, dupliquez la logique
    // ou créez une fonction de filtrage plus générique.
    // Par exemple, pour filtrer sur plusieurs colonnes :
    // Vous pourriez avoir un champ de recherche global ou des champs par colonne.
    // Le principe reste de lire les valeurs des cellules et de comparer.
