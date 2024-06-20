function checkPasswordStrength() {
    const password = document.getElementById("password").value;
    const result = zxcvbn(password);

    // Update password strength meter
    document.getElementById("password-strength-meter").value = result.score;

    // Update password strength text
    let text = "";
    switch (result.score) {
        case 0:
            text = "Very Weak";
            break;
        case 1:
            text = "Weak";
            break;
        case 2:
            text = "Moderate";
            break;
        case 3:
            text = "Strong";
            break;
        case 4:
            text = "Very Strong";
            break;
        default:
            break;
    }

    document.getElementById("password-strength-text").textContent = text;
}