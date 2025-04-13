document.addEventListener('DOMContentLoaded', () => {
    // Get input fields
    const productNameInput = document.getElementById('productNameInput');
    const productPriceInput = document.getElementById('productPriceInput');
    const productLocationInput = document.getElementById('productLocationInput');
    const productDescriptionInput = document.getElementById('productDescriptionInput');

    // Get preview elements
    const previewProductName = document.getElementById('previewProductName');
    const previewProductPrice = document.getElementById('previewProductPrice');
    const previewProductLocation = document.getElementById('previewProductLocation');
    const previewProductDescription = document.getElementById('previewProductDescription');
    const previewLocation = document.getElementById('previewLocation');

    // Event listeners to update preview
    productNameInput.addEventListener('input', () => {
        previewProductName.textContent = productNameInput.value || "Sample Product Name";
    });

    productPriceInput.addEventListener('input', () => {
        previewProductPrice.textContent = productPriceInput.value ? `${productPriceInput.value}` : "Price not set";
    });

    productLocationInput.addEventListener('input', () => {
        previewProductLocation.textContent = productLocationInput.value || "Sample Location";
    });

    productDescriptionInput.addEventListener('input', () => {
        previewProductDescription.textContent = productDescriptionInput.value || "Sample description here.";
    });

     // Event listener to update location in preview
     productLocationInput.addEventListener('input', () => {
        previewLocation.textContent = productLocationInput.value || "Purok 3 Busay Daraga, Albay";
    });
});


document.addEventListener('DOMContentLoaded', () => {
    

   
});