  // Add driver_id if exists (can be null for new drivers)
  const driverId = localStorage.getItem('driver_id');
  if (driverId) {
    formData.append('driver_id', driverId);
  }