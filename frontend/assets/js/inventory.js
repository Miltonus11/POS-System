document.addEventListener('DOMContentLoaded', function () {
    const addProductBtn = document.getElementById('add-product-btn');
    const productModal = document.getElementById('product-modal');
    const passwordModal = document.getElementById('password-modal');
    const barcodeModal = document.getElementById('barcode-modal');
    const closeModalBtns = document.querySelectorAll('.close-btn');
    const productForm = document.getElementById('product-form');
    const productsView = document.getElementById('products-view');
    const stockHistoryView = document.getElementById('stock-history-view');
    const backToProductsBtn = document.getElementById('back-to-products-btn');

    const showModal = (modal) => modal.style.display = 'flex';
    const hideModal = (modal) => modal.style.display = 'none';

    addProductBtn.addEventListener('click', () => {
        productForm.reset();
        document.getElementById('product-modal-title').textContent = 'Add Product';
        showModal(productModal);
    });

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            hideModal(productModal);
            hideModal(passwordModal);
            hideModal(barcodeModal);
        });
    });

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('product-modal-title').textContent = 'Edit Product';
            showModal(productModal);
        });
    });

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', () => {
            showModal(passwordModal);
        });
    });

    document.querySelectorAll('.stock-history-btn').forEach(button => {
        button.addEventListener('click', () => {
            productsView.style.display = 'none';
            stockHistoryView.style.display = 'block';
        });
    });

    document.querySelectorAll('.reveal-barcode-btn').forEach(button => {
        button.addEventListener('click', () => {
            showModal(barcodeModal);
        });
    });

    backToProductsBtn.addEventListener('click', () => {
        stockHistoryView.style.display = 'none';
        productsView.style.display = 'block';
    });
});
