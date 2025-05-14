// Idéalement dans assets/js/script.js, et assurez-vous que ce script est chargé par layouts/main.php

document.addEventListener('DOMContentLoaded', function () {
    const nameSearchInput = document.getElementById('materialNameSearch');
    const materialsTable = document.getElementById('materialsTable');

    if (nameSearchInput && materialsTable) {
        nameSearchInput.addEventListener('keyup', function () {
            const searchTerm = nameSearchInput.value.toLowerCase();
            const rows = materialsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[1]; // Colonne du nom (index 1)
                
                if (nameCell) {
                    const nameText = nameCell.textContent || nameCell.innerText;
                    if (nameText.toLowerCase().indexOf(searchTerm) > -1) {
                        rows[i].style.display = ''; // Afficher la ligne
                    } else {
                        rows[i].style.display = 'none'; // Cacher la ligne
                    }
                }
            }
        });
    }

    // Si vous voulez ajouter des filtres pour d'autres colonnes, dupliquez la logique
    // ou créez une fonction de filtrage plus générique.
    // Par exemple, pour filtrer sur plusieurs colonnes :
    // Vous pourriez avoir un champ de recherche global ou des champs par colonne.
    // Le principe reste de lire les valeurs des cellules et de comparer.
});