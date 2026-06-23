const CONTROL_API = 'api/control.php';

const elements = {
    relayState: document.querySelector('#emulatorRelayState'),
    relayVisual: document.querySelector('#relayVisual'),
    description: document.querySelector('#emulatorDescription'),
    gpioState: document.querySelector('#gpioState'),
    loadState: document.querySelector('#loadState'),
    loadDescription: document.querySelector('#loadDescription'),
};

async function getControlState() {
    const response = await fetch(CONTROL_API, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`API zwróciło status ${response.status}`);
    }

    return response.json();
}

function renderState(state) {
    const enabled = Boolean(state.power_enabled);

    elements.relayState.textContent = enabled
        ? 'Przekaźnik załączony'
        : 'Przekaźnik odłączony';
    elements.relayVisual.classList.toggle('off', !enabled);
    elements.relayVisual.querySelector('span').textContent = enabled ? 'ON' : 'OFF';
    elements.description.textContent = enabled
        ? 'Styk przekaźnika jest zamknięty, więc urządzenie może pobierać energię.'
        : 'Styk przekaźnika jest otwarty, więc przepływ prądu do urządzenia jest zablokowany.';
    elements.gpioState.textContent = enabled ? 'GPIO HIGH' : 'GPIO LOW';
    elements.loadState.textContent = enabled ? 'Prąd płynie' : 'Brak przepływu';
    elements.loadDescription.textContent = enabled
        ? 'Symulowany odbiornik jest podłączony do obwodu pomiarowego.'
        : 'Symulowany odbiornik został odłączony przez komendę z dashboardu.';
}

async function refreshState() {
    try {
        renderState(await getControlState());
    } catch (error) {
        elements.relayState.textContent = 'Błąd komunikacji';
        elements.description.textContent = error.message;
        elements.gpioState.textContent = '--';
        elements.loadState.textContent = '--';
    }
}

refreshState();
setInterval(refreshState, 1500);
