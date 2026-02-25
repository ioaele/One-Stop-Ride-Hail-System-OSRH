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
  
  function loginRequest() {
    const email    = document.querySelector('#email');
    const password = document.querySelector('#password');
  
    const data = {
      email:    email.value,
      password: password.value
    };
  
    const xhr = new XMLHttpRequest();
  
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
  
      if (xhr.status >= 200 && xhr.status < 300) {
        let response;
        try {
          response = JSON.parse(xhr.responseText);
        } catch (e) {
          console.error('Invalid JSON from server:', xhr.responseText);
          showSignupMessage('Unexpected response from server.', 'error');
          return;
        }
  
        if (response.status === 'success') {
          if (response.users_id) {
            localStorage.setItem('users_id', response.users_id);
          }
          if (response.username) {
            localStorage.setItem('username', response.username);
          }
          if (response.role) {
            localStorage.setItem('role', response.role);
          }
            if (data.is_driver && data.driver_id) {
                localStorage.setItem('is_driver', 'true');
                localStorage.setItem('driver_id', data.driver_id);
            } else {
                localStorage.setItem('is_driver', 'false');
                localStorage.removeItem('driver_id');
            }
            
  
          showSignupMessage(response.message || 'Login successful.', 'success');
  
          setTimeout(() => {
            const role = String(response.role).trim().toLowerCase();
            switch (role) {
              case 'passenger':
                target = 'homepage_pas.html';
                break;
              case 'system operator':
                target = 'operator_dashboard.html';
                break;
              case 'system admin':
                target = 'homepage_ad.html';
                break;
              case 'driver':
              target = 'driver.html';
              break;
              case 'company':
              target = 'company.html';
              break;
              
            }
          
            window.location.href = target;
          }, 800);
  
        } else {
          showSignupMessage(response.message || 'Login failed.', 'error');
        }
  
      } else {
        console.error('Login error:', xhr.status, xhr.responseText);
        showSignupMessage('Network/server error. Please try again.', 'error');
      }
    };
  
    xhr.open('POST', 'login.php');
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify(data));
  }
  
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#login-form');
    if (!form) return;
  
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      loginRequest();
    });
  });