document.addEventListener('DOMContentLoaded', () => {
  const storedUsername = localStorage.getItem('username') || 'User';

  const navbarUsernameEl = document.getElementById('navbar-username');
  const heroUsernameEl   = document.getElementById('hero-username');

  if (navbarUsernameEl) navbarUsernameEl.textContent = storedUsername;
  if (heroUsernameEl) heroUsernameEl.textContent = storedUsername;
});


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

function submitDriverVehicleRequest() {
  const form = document.getElementById('driverVehicleForm');
  if (!form) {
    console.error('driverVehicleForm not found');
    return;
  }

  const formData = new FormData(form);

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
        showSignupMessage(resp.message || 'Request submitted successfully.', 'success');
        setTimeout(() => { window.location.href = 'homepage_pas.html'; }, 800);
      } else {
        showSignupMessage(resp.message || 'Submission failed.', 'error');
      }
    } else {
      console.error('Submit error:', xhr.status, xhr.responseText);
      showSignupMessage('Network/server error. Please try again.', 'error');
    }
  };

  xhr.open('POST', 'driver_vehicle_request_docs.php');

  xhr.send(formData);
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#driverVehicleForm');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

   
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('driverVehicleForm');
  const euCheckbox = document.getElementById('is_eu_resident');
  const resBlock = document.getElementById('residence-permit-block');


  function toggleResidencePermit() {
    if (euCheckbox.checked) {
      resBlock.style.display = 'none';
      resBlock.querySelectorAll('input').forEach(inp => {
        if (inp.type !== 'hidden') inp.required = false;
      });
    } else {
      resBlock.style.display = '';
     
     resBlock.querySelectorAll('input[type="text"],input[type="date"],input[type="file"]').forEach(inp => inp.required = true);
    }
  }

  euCheckbox.addEventListener('change', toggleResidencePermit);
  toggleResidencePermit();

  
  form.addEventListener('submit', function (e) {
    const requiredBlocks = document.querySelectorAll('.doc-block[data-required-doc="true"]');
    let valid = true;

    requiredBlocks.forEach(block => {
      block.classList.remove('doc-block-error');
      const requiredInputs = block.querySelectorAll('input[required]');
      requiredInputs.forEach(inp => {
        if (!inp.value) {
          valid = false;
          block.classList.add('doc-block-error');
        }
      });
    });

    if (!valid) {
      e.preventDefault();
      alert('Please fill in all required document fields.');
    }

   
    const crDateInput = document.getElementById('cr_publish');
    if (crDateInput && crDateInput.value) {
      const crDate = new Date(crDateInput.value);
      const now = new Date();
      const oneMonthAgo = new Date();
      oneMonthAgo.setMonth(now.getMonth() - 1);

      if (crDate < oneMonthAgo) {
        e.preventDefault();
        crDateInput.closest('.doc-block').classList.add('doc-block-error');
        alert('Criminal record certificate must not be older than one month.');
      }
    }
  });

});
    submitDriverVehicleRequest();
  });
});
