function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-bg').forEach(function (m) {
  m.addEventListener('click', function (e) { if (e.target === m) m.classList.remove('open'); });
});
