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
  
  function forgotPasswordRequest() {
    const username    = document.querySelector('#username');
    const email       = document.querySelector('#email');
    const password    = document.querySelector('#password');
    const confirmPass = document.querySelector('#confirm_password');
  
    if (password.value !== confirmPass.value) {
      showSignupMessage('Passwords do not match.', 'error');
      confirmPass.focus();
      return;
    }
  
    const data = {
      username:         username.value,
      email:            email.value,
      password:         password.value,
      confirm_password: confirmPass.value
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
          showSignupMessage(resp.message || 'Success! You can now log in.', 'success');
          setTimeout(() => {
            window.location.href = 'login.html';
          }, 800);
        } else {
          showSignupMessage(resp.message || 'Forgot password failed.', 'error');

        }
      } else {
        console.error('Forgot password error:', xhr.status, xhr.responseText);
        showSignupMessage('Network/server error. Please try again.', 'error');
      }
    };
  
    xhr.open('POST', 'forgot_password.php');  
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify(data));
  }
  
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#forgot-form'); 
    if (!form) return;
  
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      forgotPasswordRequest();
    });
  });
  