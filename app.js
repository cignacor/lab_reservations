// Academic Laboratory Reservation System
// Frontend JavaScript - camelCase convention

const apiBaseUrl = 'bookings.php';

let selectedLaboratoryId = null;
let isAvailable = false;

document.addEventListener('DOMContentLoaded', function() {
    loadLaboratories();
    loadBookings();
    setupEventListeners();
    setMinDate();
});

function setupEventListeners() {
    document.getElementById('checkAvailabilityBtn').addEventListener('click', checkAvailability);
    document.getElementById('bookBtn').addEventListener('click', createBooking);
    document.getElementById('logoutBtn').addEventListener('click', logout);
}

function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('bookingDate').min = today;
}

async function loadLaboratories() {
    try {
        const response = await fetch(`${apiBaseUrl}?action=laboratories`);
        const result = await response.json();

        if (result.status === 'success') {
            displayLaboratories(result.data);
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Error loading laboratories');
    }
}

function displayLaboratories(laboratories) {
    const grid = document.getElementById('laboratoriesGrid');
    grid.innerHTML = '';

    laboratories.forEach(lab => {
        const card = document.createElement('div');
        card.className = 'laboratory-card';
        card.dataset.id = lab.id;
        card.onclick = () => selectLaboratory(lab.id, card);

        card.innerHTML = `
            <div class="laboratory-icon">
                <i class="fas fa-flask"></i>
            </div>
            <div class="laboratory-name">${lab.name}</div>
            <div class="laboratory-description">${lab.description}</div>
            <div class="laboratory-capacity">
                <i class="fas fa-users"></i> Capacity: ${lab.capacity} people
            </div>
        `;

        grid.appendChild(card);
    });
}

function selectLaboratory(id, card) {
    document.querySelectorAll('.laboratory-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    selectedLaboratoryId = id;
}

async function checkAvailability() {
    if (!selectedLaboratoryId) {
        showError('Please select a laboratory first');
        return;
    }

    const date = document.getElementById('bookingDate').value;
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;

    if (!date || !startTime || !endTime) {
        showError('Please fill in all fields');
        return;
    }

    if (startTime >= endTime) {
        showError('End time must be after start time');
        return;
    }

    try {
        const response = await fetch(`${apiBaseUrl}?action=availability&laboratory_id=${selectedLaboratoryId}&date=${date}&start_time=${startTime}&end_time=${endTime}`);
        const result = await response.json();

        if (result.status === 'success') {
            isAvailable = result.available;
            if (isAvailable) {
                document.getElementById('bookBtn').style.display = 'inline-block';
                showSuccess('Laboratory is available for booking');
            } else {
                document.getElementById('bookBtn').style.display = 'none';
                showError('Laboratory is not available for the selected time');
            }
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Error checking availability');
    }
}

async function createBooking() {
    if (!isAvailable || !selectedLaboratoryId) {
        showError('Please check availability first');
        return;
    }

    const date = document.getElementById('bookingDate').value;
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;

    try {
        const response = await fetch(`${apiBaseUrl}?action=book`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                laboratory_id: selectedLaboratoryId,
                date: date,
                start_time: startTime,
                end_time: endTime
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            showSuccess('Booking created successfully');
            loadBookings();
            resetForm();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Error creating booking');
    }
}

async function loadBookings() {
    try {
        const response = await fetch(`${apiBaseUrl}?action=bookings`);
        const result = await response.json();

        if (result.status === 'success') {
            displayBookings(result.data);
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Error loading bookings');
    }
}

function displayBookings(bookings) {
    const container = document.getElementById('bookingsList');
    container.innerHTML = '';

    if (bookings.length === 0) {
        container.innerHTML = '<p>No active bookings found.</p>';
        return;
    }

    bookings.forEach(booking => {
        const item = document.createElement('div');
        item.className = 'booking-item';

        item.innerHTML = `
            <div class="booking-info">
                <h4>${booking.laboratory_name}</h4>
                <div class="booking-details">
                    Date: ${formatDate(booking.date)}<br>
                    Time: ${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}<br>
                    Capacity: ${booking.capacity} people
                </div>
            </div>
            <div class="booking-actions">
                <button class="btn btn-danger" onclick="cancelBooking(${booking.id})">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        `;

        container.appendChild(item);
    });
}

async function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking?')) {
        return;
    }

    try {
        const response = await fetch(`${apiBaseUrl}?action=cancel`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: bookingId })
        });

        const result = await response.json();

        if (result.status === 'success') {
            showSuccess('Booking cancelled successfully');
            loadBookings();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Error cancelling booking');
    }
}

function resetForm() {
    document.querySelectorAll('.laboratory-card').forEach(c => c.classList.remove('selected'));
    selectedLaboratoryId = null;
    isAvailable = false;
    document.getElementById('bookingDate').value = '';
    document.getElementById('startTime').value = '';
    document.getElementById('endTime').value = '';
    document.getElementById('bookBtn').style.display = 'none';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    return timeString.substring(0, 5); // HH:MM format
}

function showSuccess(message) {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModal').classList.add('show');
}

function showError(message) {
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorModal').classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        // Implement logout logic if needed
        window.location.reload();
    }
}
