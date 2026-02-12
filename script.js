// Gestion des formulaires AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de création de document
    const documentForm = document.getElementById('documentForm');
    if (documentForm) {
        documentForm.addEventListener('submit', function(e) {
            // Validation côté client
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
            }
        });
    }
    
    // Auto-génération de la date pour les nouveaux documents
    const dateField = document.getElementById('date_document');
    if (dateField && !dateField.value) {
        const today = new Date().toISOString().split('T')[0];
        dateField.value = today;
    }
    
    // Gestion des messages flash
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Fonction de recherche de documents (si implémentée)
    const searchInput = document.getElementById('searchDocuments');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const documents = document.querySelectorAll('.document-card');
            
            documents.forEach(doc => {
                const title = doc.querySelector('h3').textContent.toLowerCase();
                const content = doc.querySelector('.document-excerpt').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || content.includes(searchTerm)) {
                    doc.style.display = 'block';
                } else {
                    doc.style.display = 'none';
                }
            });
        });
    }
});

// Fonctions utilitaires
function showLoading() {
    const loader = document.createElement('div');
    loader.className = 'loading-overlay';
    loader.innerHTML = '<div class="loader"></div>';
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.querySelector('.loading-overlay');
    if (loader) loader.remove();
}

// Style pour le loader
const style = document.createElement('style');
style.textContent = `
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loader {
    border: 5px solid #f3f3f3;
    border-top: 5px solid #3498db;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error {
    border-color: #e74c3c !important;
}
`;
document.head.appendChild(style);