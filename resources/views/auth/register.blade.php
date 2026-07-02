<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Registration</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f4f6f8; margin: 0; padding: 40px 0; }
        .container {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #333; margin-bottom: 25px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 5px; font-size: 14px; color: #444; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        input.error { border-color: #e74c3c; }
        .error-text { color: #e74c3c; font-size: 12px; margin-top: 4px; display: block; }
        .captcha-box { display: flex; align-items: center; gap: 10px; }
        .captcha-box img { border: 1px solid #ccc; border-radius: 5px; }
        .refresh-btn {
            background: #eee; border: 1px solid #ccc; border-radius: 5px;
            cursor: pointer; padding: 8px 12px; font-size: 13px;
        }
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .checkbox-group label { margin: 0; font-weight: normal; font-size: 13px; }
        button[type="submit"] {
            width: 100%;
            background: #2563eb;
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
        }
        button[type="submit"]:disabled { background: #93b4f0; cursor: not-allowed; }
        #alertBox {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 16px;
            font-size: 14px;
            display: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .hint { font-size: 12px; color: #777; margin-top: 4px; display: block; }
    </style>
</head>
<body>

<div class="container">
    <h2>Employee Registration</h2>

    <div id="alertBox"></div>

    <form id="registerForm" novalidate>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="user_name" id="user_name">
            <span class="error-text" id="err_user_name"></span>
        </div>

        <div class="form-group">
            <label>Employee ID</label>
            <input type="text" name="employee_id" id="employee_id" placeholder="e.g. EMP1001">
            <span class="error-text" id="err_employee_id"></span>
        </div>

        <div class="form-group">
            <label>Company Code</label>
            <input type="text" name="company_code" id="company_code" placeholder="e.g. COMP001">
            <span class="error-text" id="err_company_code"></span>
        </div>

        

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="email">
            <span class="error-text" id="err_email"></span>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" id="password">
            <span class="hint">Min 8, max 15 characters. At least 1 uppercase, 1 lowercase, 1 digit, 1 special (-@#$%^+=)</span>
            <span class="error-text" id="err_password"></span>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation">
            <span class="error-text" id="err_password_confirmation"></span>
        </div>

        <div class="form-group">
            <label>Captcha</label>
            <div class="captcha-box">
                <img id="captchaImage" src="" alt="captcha" width="150" height="50">
                <button type="button" class="refresh-btn" id="refreshCaptcha">↻ Refresh</button>
            </div>
            <input type="text" name="captcha_input" id="captcha_input" placeholder="Enter captcha text" style="margin-top:8px;">
            <input type="hidden" name="captcha_key" id="captcha_key">
            <span class="error-text" id="err_captcha_input"></span>
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" name="terms" id="terms">
            <label for="terms">I agree to the Terms and Conditions</label>
        </div>
        <span class="error-text" id="err_terms"></span>

        <button type="submit" id="submitBtn">Register</button>
    </form>
</div>

<script>
const API_BASE = "{{ url('/api') }}"; // e.g. http://127.0.0.1:8000/api

const form = document.getElementById('registerForm');
const submitBtn = document.getElementById('submitBtn');
const alertBox = document.getElementById('alertBox');

// ---------- Load Captcha ----------
async function loadCaptcha() {
    try {
        const res = await fetch(`${API_BASE}/captcha`);
        const data = await res.json();
        document.getElementById('captchaImage').src = data.captcha_image;
        document.getElementById('captcha_key').value = data.captcha_key;
        document.getElementById('captcha_input').value = '';
    } catch (err) {
        showAlert('Failed to load captcha. Please refresh the page.', 'error');
    }
}

document.getElementById('refreshCaptcha').addEventListener('click', loadCaptcha);

// Load captcha as soon as page opens
loadCaptcha();

// ---------- Helper: clear all field errors ----------
function clearErrors() {
    document.querySelectorAll('.error-text').forEach(el => el.textContent = '');
    document.querySelectorAll('input').forEach(el => el.classList.remove('error'));
}

function showAlert(message, type) {
    alertBox.textContent = message;
    alertBox.className = type === 'success' ? 'alert-success' : 'alert-error';
    alertBox.style.display = 'block';
}

// ---------- Submit Form ----------
form.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();
    alertBox.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Registering...';

    const payload = {
        user_name: document.getElementById('user_name').value,
        company_code: document.getElementById('company_code').value,
        employee_id: document.getElementById('employee_id').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        password_confirmation: document.getElementById('password_confirmation').value,
        captcha_key: document.getElementById('captcha_key').value,
        captcha_input: document.getElementById('captcha_input').value,
        terms: document.getElementById('terms').checked,
    };

    try {
        const res = await fetch(`${API_BASE}/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const result = await res.json();

        if (res.status === 201) {
            showAlert('Registration successful! Token generated.', 'success');
            console.log('Access Token:', result.data.access_token);
            form.reset();
            loadCaptcha();
        } else if (res.status === 422) {
            // Validation errors — Laravel returns { message, errors: { field: [msgs] } }
            const errors = result.errors || {};
            Object.keys(errors).forEach(field => {
                const errEl = document.getElementById('err_' + field);
                const inputEl = document.getElementById(field);
                if (errEl) errEl.textContent = errors[field][0];
                if (inputEl) inputEl.classList.add('error');
            });
            showAlert('Please fix the errors below.', 'error');
            loadCaptcha(); // captcha already consumed/invalidated on failed attempt too, refresh it
        } else {
            showAlert(result.message || 'Something went wrong.', 'error');
            loadCaptcha();
        }
    } catch (err) {
        showAlert('Network error. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Register';
    }
});
</script>

</body>
</html>