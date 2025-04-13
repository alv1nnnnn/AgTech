document.addEventListener('DOMContentLoaded', function() {
    // Populate Municipality dropdown options
    var municipalitySelect = document.getElementById('municipality');
    var municipalities = [
        "Select Municipality",
        "Bacacay",
        "Camalig",
        "Daraga",
        "Guinobatan",
        "Jovellar",
        "Legazpi",
        "Libon",
        "Ligao",
        "Malilipot",
        "Malinao",
        "Manito",
        "Oas",
        "Pio Duran",
        "Polangui",
        "Rapu-Rapu",
        "Santo Domingo (Libog)",
        "Tiwi"
    ];

    municipalities.forEach(function(municipality) {
        var option = document.createElement('option');
        option.text = municipality;
        option.value = municipality;
        municipalitySelect.add(option);
    });

    // Set default value for Municipality dropdown
    var defaultMunicipality = "Select Municipality";
    municipalitySelect.value = defaultMunicipality;
    municipalitySelect.classList.add('fade-input'); // Initially add fade class

    // Set default value for Province input
    var provinceInput = document.getElementById('province');
    provinceInput.value = 'Albay';
    provinceInput.classList.add('fade-input'); // Initially add fade class

    // Remove fade class when input fields are focused or typed in
    var formInputs = document.querySelectorAll('input, select');
    formInputs.forEach(function(input) {
        input.addEventListener('focus', removeFade);
        input.addEventListener('input', removeFade);
    });

    function removeFade() {
        provinceInput.classList.remove('fade-input');
        municipalitySelect.classList.remove('fade-input');
    }
});