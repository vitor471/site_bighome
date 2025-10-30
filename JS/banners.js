document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector('input[name="foto"]');
  const previewBox = document.querySelector(".banner-thumb");
  if (!input || !previewBox) return;

  input.addEventListener("change", () => {
    const file = input.files && input.files[0];

    if (!file) {
      previewBox.innerHTML = '<span class="text-muted">Prévia</span>';
      return;
    }
    if (!file.type.startsWith("image/")) {
      previewBox.innerHTML = '<span class="text-danger small">Arquivo inválido</span>';
      input.value = "";
      return;
    }

    const reader = new FileReader();
    reader.onload = e => {
      previewBox.innerHTML = `<img src="${e.target.result}" alt="Prévia do banner">`;
    };
    reader.readAsDataURL(file);
  });
});


/* ========= LISTAR CATEGORIAS ========= */
function listarcategorias(nomeid) {
  (async () => {
    const sel = document.querySelector(nomeid);
    if (!sel) return;

    try {
      const r = await fetch("../PHP/cadastro_categorias.php?listar=1");
      if (!r.ok) throw new Error("Falha ao listar categorias!");
      sel.innerHTML = await r.text();
    } catch (e) {
      sel.innerHTML = "<option disabled>Erro ao carregar</option>";
    }
  })();
}






function listarCupons(tbcupom) {
  document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById(tbcupom);
    const url   = '../PHP/cadastro_cupom.php?listar=1';

    // Escapa texto (evita injeção de HTML)
    const esc = s => (s || '').replace(/[&<>"']/g, c => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));

    // Converte data YYYY-MM-DD → DD/MM/YYYY
    const dtbr = iso => {
      if (!iso) return '-';
      const [y,m,d] = String(iso).split('-');
      return (y && m && d) ? `${d}/${m}/${y}` : '-';
    };

    // Monta a <tr> de cada cupom
    const row = c => `
      <tr>
        <td>${c.id}</td>
        <td>${esc(c.nome)}</td>
        <td>R$ ${parseFloat(c.valor).toFixed(2).replace('.', ',')}</td>
        <td>${dtbr(c.data_validade)}</td>
        <td>${c.quantidade}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-warning" data-id="${c.id}">Editar</button>
          <button class="btn btn-sm btn-danger"  data-id="${c.id}">Excluir</button>
        </td>
      </tr>`;

    // Busca os dados e preenche a tabela
    fetch(url, { cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || 'Erro ao listar cupons');
        const arr = d.cupons || [];
        tbody.innerHTML = arr.length
          ? arr.map(row).join('')
          : `<tr><td colspan="6" class="text-center text-muted">Nenhum cupom cadastrado.</td></tr>`;
      })
      .catch(err => {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
      });
  });
}














/* ========= LISTAR BANNERS ========= */
const byId = new Map();

function listarBanners(tbbanner) {
  const tbody = document.getElementById(tbbanner);
  if (!tbody) return;

  const url = "../PHP/banners.php?listar=1";
  byId.clear();

  const esc = s => (s || '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));

  const ph = () => 'data:image/svg+xml;base64,' + btoa(
    `<svg xmlns="http://www.w3.org/2000/svg" width="96" height="64">
       <rect width="100%" height="100%" fill="#eee"/>
       <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
             font-family="sans-serif" font-size="12" fill="#999">SEM IMAGEM</text>
     </svg>`
  );

  const dtbr = iso => {
    if (!iso) return '-';
    const [y,m,d] = String(iso).split('-');
    return (y && m && d) ? `${d}/${m}/${y}` : '-';
  };

  const row = b => {
    const src  = b.imagem ? `data:image/*;base64,${b.imagem}` : ph();
    const cat  = b.categoria_nome || '-';
    const link = b.link ? `<a href="${esc(b.link)}" target="_blank" rel="noopener">abrir</a>` : '-';

    byId.set(String(b.id), b);

    return `
      <tr>
        <td><img src="${src}" alt="banner"
                 style="width:96px;height:64px;object-fit:cover;border-radius:6px"></td>
        <td>${esc(b.descricao || '-')}</td>
        <td class="text-nowrap">${dtbr(b.data_validade)}</td>
        <td>${esc(cat)}</td>
        <td>${link}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-warning btn-edit" data-id="${b.id}">Selecionar</button>
        </td>
      </tr>`;
  };

  fetch(url, { cache: 'no-store' })
    .then(r => r.json())
    .then(d => {
      if (!d.ok) throw new Error(d.error || 'Erro ao listar banners');
      const arr = d.banners || [];
      tbody.innerHTML = arr.length
        ? arr.map(row).join('')
        : `<tr><td colspan="6" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
    });

  tbody.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button');
    if (!btn) return;

    if (btn.classList.contains('btn-edit')) {
      const id = btn.getAttribute('data-id');
      const banner = byId.get(String(id));
      if (!banner) {
        alert('Não foi possível localizar os dados deste banner.');
        return;
      }
      preencherFormBanner(banner);
    }
  });
}

/* ========= PRÉVIA ========= */
function setPreview(src) {
  const previewBox =
    document.getElementById('previewBanner') ||
    document.querySelector('.banner-thumb');
  if (!previewBox) return;

  const ph = () =>
    'data:image/svg+xml;base64,' + btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="320" height="160">
         <rect width="100%" height="100%" fill="#f2f2f2"/>
         <text x="50%" y="50%" font-size="14" fill="#999"
               text-anchor="middle" dominant-baseline="middle">Prévia</text>
       </svg>`
    );

  previewBox.innerHTML = '';
  const img = document.createElement('img');
  img.src = src || ph();
  img.alt = 'Prévia do banner';
  img.className = 'img-fluid';
  img.style.maxHeight = '160px';
  img.style.objectFit  = 'contain';
  previewBox.appendChild(img);
}

/* ========= PREENCHER FORMULÁRIO ========= */
function preencherFormBanner(banner) {
  const form      = document.getElementById('formBanner') || document.querySelector('form');
  const acaoInput = document.getElementById('acao')      || form.querySelector('input[name="acao"]');
  const idInput   = document.getElementById('idBanner')  || form.querySelector('input[name="id"]');

  form.querySelector('input[name="descricao"]').value = banner.descricao || '';
  form.querySelector('input[name="data"]').value      = banner.data_validade || '';
  form.querySelector('input[name="link"]').value      = banner.link || '';

  const sel = form.querySelector('select[name="categoriab"]');
  if (sel) sel.value = (banner.categoria_id ?? '') + '';

  idInput.value   = banner.id;
  acaoInput.value = 'atualizar';

  const file = form.querySelector('input[name="foto"]');
  if (file) file.value = '';

  setPreview(banner.imagem ? `data:image/*;base64,${banner.imagem}` : null);

  const btnCadastrar = document.getElementById('btnCadastrar');
  if (btnCadastrar) {
    btnCadastrar.textContent = 'Salvar alterações';
    btnCadastrar.classList.remove('btn-primary');
    btnCadastrar.classList.add('btn-success');
  }

  form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* ========= BOTÃO EDITAR ========= */
document.addEventListener('DOMContentLoaded', () => {
  const form      = document.getElementById('formBanner') || document.querySelector('form');
  const btnEditar = document.getElementById('btnEditar');
  const acaoInput = document.getElementById('acao')      || form.querySelector('input[name="acao"]');
  const idInput   = document.getElementById('idBanner')  || form.querySelector('input[name="id"]');

  if (!form || !btnEditar) return;

  btnEditar.addEventListener('click', () => {
    if (!idInput.value) {
      alert('Clique em "Selecionar" na tabela para carregar um banner primeiro.');
      return;
    }
    acaoInput.value = 'atualizar';
    form.submit();
  });
});

/* ========= BOTÃO EXCLUIR ========= */
document.addEventListener('DOMContentLoaded', () => {
  const form        = document.getElementById('formBanner') || document.querySelector('form');
  const btnExcluir  = document.getElementById('btnExcluir');
  const idInput     = document.getElementById('idBanner')  || form.querySelector('input[name="id"]');
  const previewBox  = document.getElementById('previewBanner') || document.querySelector('.banner-thumb');
  const btnCadastrar = document.getElementById('btnCadastrar');
  const acaoInput   = document.getElementById('acao') || form.querySelector('input[name="acao"]');

  if (!form || !btnExcluir) return;

  btnExcluir.addEventListener('click', async () => {
    const id = idInput.value;
    if (!id) {
      alert('Selecione um banner na tabela para excluir.');
      return;
    }

    if (!confirm('Tem certeza que deseja excluir este banner?')) return;

    try {
      const fd = new FormData();
      fd.append('acao', 'excluir');
      fd.append('id', id);

      const r = await fetch('../PHP/banners.php', {
        method: 'POST',
        body: fd
      });

      if (!r.ok) throw new Error('Falha na exclusão.');

      alert('Banner excluído com sucesso!');

      form.reset();
      idInput.value = '';
      acaoInput.value = '';
      if (previewBox) previewBox.innerHTML = '<span class="text-muted">Prévia</span>';

      if (btnCadastrar) {
        btnCadastrar.textContent = 'Cadastrar';
        btnCadastrar.classList.remove('btn-success');
        btnCadastrar.classList.add('btn-primary');
      }

      listarBanners('tbBanners');
    } catch (e) {
      alert('Erro ao excluir: ' + (e.message || e));
    }
  });
});




 listarCupons('listcupom')

/* ========= INICIALIZA ========= */
document.addEventListener('DOMContentLoaded', () => {
  listarBanners('tbBanners');
  listarcategorias("#categoriaBanner");
  listarcategorias("#categoriasPromocoes");
 
});
