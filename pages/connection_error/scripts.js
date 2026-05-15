let seconds = 30;
const timerElement = document.getElementById('timer');
const returnUrl = ('/pages/list_workloads/');

function updateTimer() {
    if (seconds > 0) {
        timerElement.textContent = `Автоматическая перезагрузка через ${seconds} сек...`;
        seconds--;
        setTimeout(updateTimer, 1000);
    } else {
        window.location.href = returnUrl;
    }
}

updateTimer();