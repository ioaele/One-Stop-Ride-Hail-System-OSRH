
function showAdminMessage(message, type) {
  const alertBox = document.getElementById('alert');
  const alertText = document.getElementById('alert-text');

  if (!alertBox || !alertText) {
    alert(message);
    return;
  }

  alertText.textContent = message;
  alertBox.classList.remove('alert-success', 'alert-error');
  alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-error');
  alertBox.style.display = 'block';
}

function createSystemOperator() {
  const usernameInput = document.getElementById('so-username');
  if (!usernameInput) {
    console.error('#so-username not found');
    return;
  }

  const soUsername = usernameInput.value.trim();

  if (soUsername === '') {
    showAdminMessage('Please enter a username.', 'error');
    return;
  }

  const saUsersId = localStorage.getItem('users_id');

  const data = {
    so_username: soUsername,
    sa_users_id: saUsersId
  };


  const xhr = new XMLHttpRequest();

  xhr.onreadystatechange = function () {
    if (xhr.readyState !== 4) return;

   
    let resp;
    try {
      resp = JSON.parse(xhr.responseText);
    } catch (e) {
      console.error('Invalid JSON from server:', xhr.responseText);
      showAdminMessage('Unexpected response from server.', 'error');
      return;
    }


    if (resp.status === 'success') {
      showAdminMessage(resp.message || 'User is now a System Operator.', 'success');
      usernameInput.value = '';
    } else {
  
      showAdminMessage(resp.message || 'Operation failed.', 'error');
    
    }
  };

  xhr.open('POST', 'createSystemOp.php'); 
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.send(JSON.stringify(data));
}


document.addEventListener('DOMContentLoaded', () => {

  const storedUsername = localStorage.getItem('username') || 'User';
  const navbarUsernameEl = document.getElementById('navbar-username');


  if (navbarUsernameEl) {
    navbarUsernameEl.textContent = storedUsername;
  } else {
    console.warn('#navbar-username not found');
  }

  
  const form = document.getElementById('so-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      createSystemOperator();
    });
  } else {
    console.warn('#so-form not found');
  }
});
