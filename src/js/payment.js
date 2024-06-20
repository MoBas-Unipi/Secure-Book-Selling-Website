function formatExpirationDate(event) {
    const input = event.target;
    const value = input.value.replace(/\D/g, '');

    if (value.length >= 2) {
        input.value = value.slice(0, 2) + '/' + value.slice(2, 4);
    } else {
        input.value = value;
    }

}

function checkExpirationDate(expirationDate) {
    const today = new Date();
    const currentYear = today.getFullYear() % 100;
    const currentMonth = today.getMonth() + 1; // In JavaScript, months are zero-based, so we add 1.

    const [inputMonth, inputYear] = expirationDate.split('/').map(Number);

    if (
        inputYear < currentYear ||
        (inputYear === currentYear && (inputMonth < currentMonth || inputMonth < 1 || inputMonth > 12))
    ) {
        // Invalid expiration date (past date or invalid month)
        alert("The entered expiration date is invalid. Please enter a valid future date.");
    }
}

function formatCardNumber(event) {
    const input = event.target;
    let value = input.value.replace(/\s/g, ''); // Remove existing spaces
    value = value.replace(/(\d{4})/g, '$1 ');  // Add a space every 4 digits
    input.value = value.trim(); // Trim any leading/trailing spaces
}