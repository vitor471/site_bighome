// ==================== LISTAR BANNERS ====================
const byId = new Map();

function listarBanners(tabelaId) {
  const tbody = document.getElementById(tabelaId);
  if (!tbody) return;

  const url = '../PHP/cadastro_banners.php?listar=1&format=json';

  const esc = s => (s || '').replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));

  const row = b => `
    <tr>
      <td><img src="data:image/png;base64,${b.imagem || ''}" class="mini-banner rounded" style="width:100px; height:50px; object-fit:cover;"></td>
      <td>${esc(b.descricao || '-')}</td>
      <td>${esc(b.link || '-')}</td>
      <td>${esc(b.categoria_nome || '—')}</td>
      <td>${b.data_validade ? new Date(b.data_validade).toLocaleDateString('pt-BR') : '-'}</td>
      <td class="text-end">
        <div class="btn-group btn-group-sm">
          <button class="btn btn-sm btn-warning btn-edit" data-id="${b.id}">Selecionar</button>
        </div>
      </td>
    </tr>
  `;

  fetch(url, { cache: 'no-store' })
    .then(r => r.json())
    .then(d => {
      if (!d.ok) throw new Error(d.error || 'Erro ao listar banners');
      const banners = d.banners || [];
      tbody.innerHTML = banners.length
        ? banners.map(row).join('')
        : `<tr><td colspan="6" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;

      byId.clear();
      banners.forEach(b => byId.set(String(b.id), b));
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
    });

  tbody.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button.btn-edit');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    const banner = byId.get(String(id));
    if (!banner) return alert('Não foi possível localizar os dados deste banner.');
    preencherFormBanner(banner);
  });
}

// ==================== LISTAR CATEGORIAS ====================
async function listarcategorias(selector) {
  const sel = document.querySelector(selector);
  if (!sel) return;
  try {
    const r = await fetch("../PHP/cadastro_categorias.php?listar=1");
    if (!r.ok) throw new Error("Falha ao listar categorias!");
    sel.innerHTML = await r.text();
  } catch (e) {
    sel.innerHTML = "<option disabled>Erro ao carregar</option>";
  }
}

// ==================== PRÉVIA DA IMAGEM ====================
function initPreview(inputId, previewId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  if (!input || !preview) return;

  input.addEventListener("change", e => {
    const file = e.target.files[0];
    if (!file) {
      preview.innerHTML = '<span class="text-muted">Prévia</span>';
      return;
    }
    const reader = new FileReader();
    reader.onload = ev => {
      preview.innerHTML = `<img src="${ev.target.result}" alt="Prévia" class="img-fluid" style="width:100%; height:100%; object-fit:cover;">`;
    };
    reader.readAsDataURL(file);
  });
}

// ==================== PREENCHER FORMULÁRIO ====================
function preencherFormBanner(banner) {
  const form = document.getElementById('formBanner');
  if (!form) return;

  form.querySelector('input[name="descricao"]').value = banner.descricao || '';
  form.querySelector('input[name="data_validade"]').value = banner.data_validade || '';
  form.querySelector('input[name="link"]').value = banner.link || '';

  const sel = form.querySelector('select[name="categoriaprodutos_id"]');
  if (sel) sel.value = banner.categoria_id || '';

  form.querySelector('input[name="id"]').value = banner.id;
  form.querySelector('input[name="acao"]').value = 'atualizar';

  const file = form.querySelector('input[name="imagem"]');
  if (file) file.value = '';

  const previewBox = document.getElementById('previewBanner');
  if (previewBox) previewBox.innerHTML = banner.imagem ? `<img src="data:image/*;base64,${banner.imagem}" class="img-fluid" style="width:100%; height:100%; object-fit:cover;">` : '<span class="text-muted">Prévia</span>';

  const btnCadastrar = document.getElementById('btnCadastrar');
  if (btnCadastrar) {
    btnCadastrar.textContent = 'Salvar alterações';
    btnCadastrar.classList.remove('btn-primary');
    btnCadastrar.classList.add('btn-success');
  }

  form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ==================== INICIALIZAÇÃO ====================
document.addEventListener('DOMContentLoaded', () => {
  listarBanners('listbanners');
  listarcategorias('select[name="categoriaprodutos_id"]');
  initPreview('inputBanner', 'previewBanner');

  const form = document.getElementById('formBanner');
  const btnExcluir = document.getElementById('btnExcluir');

  if (btnExcluir && form) {
    btnExcluir.addEventListener('click', async () => {
      const idInput = form.querySelector('input[name="id"]');
      const acaoInput = form.querySelector('input[name="acao"]');
      const previewBox = document.getElementById('previewBanner');
      const id = idInput.value;
      if (!id) return alert('Selecione um banner primeiro.');
      if (!confirm('Deseja realmente excluir este banner?')) return;

      try {
        const fd = new FormData();
        fd.append('acao', 'excluir');
        fd.append('id', id);

        const r = await fetch('../php/cadastro_banners.php', { method:'POST', body: fd });
        if (!r.ok) throw new Error('Falha na exclusão.');

        alert('Banner excluído com sucesso!');
        form.reset();
        idInput.value = '';
        acaoInput.value = '';
        if (previewBox) previewBox.innerHTML = '<span class="text-muted">Prévia</span>';

        const btnCadastrar = document.getElementById('btnCadastrar');
        if (btnCadastrar) {
          btnCadastrar.textContent = 'Cadastrar';
          btnCadastrar.classList.remove('btn-success');
          btnCadastrar.classList.add('btn-primary');
        }

        listarBanners('listbanners');
      } catch (e) {
        alert('Erro ao excluir: ' + (e.message || e));
      }
    });
  }
});
