function showSignupMessage(message, type) {
  const alertBox = document.querySelector('#alert');
  const alertText = document.querySelector('#alert-text');

  if (alertBox && alertText) {
    alertText.textContent = message;
    alertBox.classList.remove('alert-success', 'alert-error');
    alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-error');
    alertBox.style.display = 'flex';
  } else {
    alert(message);
  }
}

function signupRequest() {
  const lastName    = document.querySelector('#last_name');
  const firstName   = document.querySelector('#first_name');
  const gender      = document.querySelector('#gender');
  const datebirth   = document.querySelector('#datebirth');
  const username    = document.querySelector('#username');
  const password    = document.querySelector('#password');
  const confirmPass = document.querySelector('#confirm_password');
  const email       = document.querySelector('#email');
  const phone       = document.querySelector('#phone_number');
  const country     = document.querySelector('#country');
  const city        = document.querySelector('#city');
  const postCode    = document.querySelector('#post_code');
  const street      = document.querySelector('#street');
  const number      = document.querySelector('#number');


  if (password.value !== confirmPass.value) {
    showSignupMessage('Passwords do not match.', 'error');
    confirmPass.focus();
    return;
  }


  const data = {
    last_name:        lastName.value,
    first_name:       firstName.value,
    gender:           gender.value,
    datebirth:        datebirth.value,
    username:         username.value,
    password:         password.value,
    confirm_password: confirmPass.value,
    email:            email.value,
    phone_number:     phone.value,
    country:          country.value,
    city:             city.value,
    post_code:        postCode.value,
    street:           street.value,
    number:           number.value
  };

  const xhr = new XMLHttpRequest();

  xhr.onreadystatechange = function () {
    if (xhr.readyState !== 4) return;

    if (xhr.status >= 200 && xhr.status < 300) {
      let resp;
      try {
        resp = JSON.parse(xhr.responseText);
      } catch (e) {
        console.error('Invalid JSON from server:', xhr.responseText);
        showSignupMessage('Unexpected response from server.', 'error');
        return;
      }

      if (resp.status === 'success') {
        showSignupMessage(resp.message || 'Signup successful.', 'success');
        setTimeout(() => {
          window.location.href = 'login.html';
        }, 800);
      } else {
        showSignupMessage(resp.message || 'Signup failed.', 'error');
      }
    } else {
      console.error('Signup error:', xhr.status, xhr.responseText);
      showSignupMessage('Network/server error. Please try again.', 'error');
    }
  };

  xhr.open('POST', 'signup.php');
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.send(JSON.stringify(data));
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#signup-form');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    signupRequest();
  });
});
