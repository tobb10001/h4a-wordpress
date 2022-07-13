document.querySelectorAll('button.h4ac-edit-team').forEach((item) => {
    item.addEventListener('click', editTeam);
});
let buttonNew = document.getElementById('h4ac-new-team');
buttonNew.addEventListener('click', newTeam);

function _setupForm(dataset) {
    let form = document.getElementById('h4ac-team-form');

    let fields = ['id', 'internalName', 'identificators', 'leagueUrl', 'cupUrl'];

    if (dataset !== null) {
        for (const field of fields) {
            form.elements[field].value = event.target.dataset[field.toLowerCase()];
        }
    } else {
        for (const field of fields) {
            form.elements[field].value = '';
        }
        form.elements['id'] = '-1';
    }
}

function editTeam(event) {
    _setupForm(event.target.dataset);
    buttonNew.style.display = 'initial';
}

function newTeam(event) {
    _setupForm(null);
    buttonNew.style.display = 'none';
}
