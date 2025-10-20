async function listarCategorias(selectId) {
  const sel = document.querySelector(selectId);
  const esc = s => (String(s || '')).replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));

  try {
    const response = await fetch("../PHP/cadastro_categorias.php?listar=1&format=json", { cache: 'no-store' });
    if (!response.ok) throw new Error('Falha ao listar categorias!');
    const data = await response.json();
    if (!data.ok) throw new Error(data.error || 'Erro no formato dos dados');

    // O value agora é o id do banco
    sel.innerHTML = data.categorias.length
      ? data.categorias.map(cat => `<option value="${esc(cat.id)}">${esc(cat.nome)}</option>`).join('')
      : '<option disabled>Nenhuma categoria cadastrada</option>';

  } catch(err) {
    sel.innerHTML = `<option disabled>Erro ao carregar: ${esc(err.message)}</option>`;
    console.error(err);
  }
}







// Exemplo de uso:
// listarCategorias('select[name="categorias[]"]');



function listarNomeMarcas(nomeid) {
  (async () => {
    const sel = document.querySelector(nomeid);
    try {
      const r = await fetch("../PHP/cadastro_marcas.php?listar=1");

      if (!r.ok) throw new Error("Falha na conexão com o servidor!");

      // Converte o retorno para JSON
      const resposta = await r.json();

      // Verifica se o PHP retornou sucesso
      if (!resposta.ok) throw new Error("Erro ao listar marcas!");

      const marcas = resposta.marcas;

      // Monta as opções
      let opcoes = "<option value='' disabled selected>Selecione a marca</option>";
      for (const m of marcas) {
        opcoes += `<option value="${m.idMarcas}">${m.nome}</option>`;
      }

      sel.innerHTML = opcoes;

    } catch (e) {
      console.error("Erro ao listar marcas:", e);
      sel.innerHTML = "<option disabled>Erro ao carregar marcas</option>";
    }
  })();
}










// função de listar marcas em tabelas
function listarMarcas(nometabelamarcas){
// Espera o HTML carregar para só então buscar e preencher a tabela
document.addEventListener('DOMContentLoaded', () => {
  // <tbody> onde as linhas serão inseridas
  const tbody = document.getElementById('tabelaMarcas');

  // Endpoint que devolve JSON { ok, count, marcas[] }
  const url = '../PHP/cadastro_marcas.php?listar=1';

  // --- util 1) esc(): escapa caracteres especiais no texto (evita quebrar o HTML)
  const esc = s => (s||'').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));

  // --- util 2) ph(): gera um SVG base64 com as iniciais, usado quando não há imagem
  const ph  = n => 'data:image/svg+xml;base64,' + btoa(
    `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60">
       <rect width="100%" height="100%" fill="#eee"/>
       <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
             font-family="sans-serif" font-size="12" fill="#999">
         ${(n||'?').slice(0,2).toUpperCase()}
       </text>
     </svg>`
  );

  // --- util 3) row(): recebe 1 marca e retorna o HTML <tr> correspondente
  // Usa a imagem em base64 se existir; senão usa o placeholder SVG
  const row = m => `
    <tr>
      <td>
        <img
          src="${m.imagem ? `data:${m.mime||'image/jpeg'};base64,${m.imagem}` : ph(m.nome)}"
          alt="${esc(m.nome||'Marca')}"
          style="width:60px;height:60px;object-fit:cover;border-radius:8px">
      </td>
      <td>${esc(m.nome||'-')}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-warning" data-id="${m.idMarcas}">Editar</button>
        <button class="btn btn-sm btn-danger"  data-id="${m.idMarcas}">Excluir</button>
      </td>
    </tr>`;

  // Faz a requisição ao PHP (sem cache) e preenche a tabela
  fetch(url, { cache: 'no-store' })
    // Converte a resposta em JSON
    .then(r => r.json())
    // Trata o JSON e renderiza
    .then(d => {
      // Se o backend sinalizou erro, lança para o .catch
      if (!d.ok) throw new Error(d.error || 'Erro ao listar');

      // Se houver marcas, monta as linhas; senão, mostra mensagem de vazio
      tbody.innerHTML = d.marcas?.length
        ? d.marcas.map(row).join('')            // junta todas as <tr> num único HTML
        : `<tr><td colspan="3">Nenhuma marca cadastrada.</td></tr>`;
    })
    // Qualquer erro (rede, JSON inválido, etc.) cai aqui
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="3">Falha ao carregar: ${esc(err.message)}</td></tr>`;
    });
});
}


// Executa quando a página carregar
document.addEventListener("DOMContentLoaded", listarMarcas);








// ==================== LISTAR PRODUTOS ====================
function listarProdutos(tbprodutosId) {
  document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById(tbprodutosId);
    const url = '../PHP/cadastro_produtos.php?format=json';

    // --- util 1) esc(): escapa caracteres especiais no HTML
    const esc = s => (s || '').toString().replace(/[&<>"']/g, c => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));

    // --- util 2) ph(): placeholder SVG quando não há imagem
    const ph = n => 'data:image/svg+xml;base64,' + btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60">
         <rect width="100%" height="100%" fill="#eee"/>
         <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
               font-family="sans-serif" font-size="12" fill="#999">
           ${(n||'?').slice(0,2).toUpperCase()}
         </text>
       </svg>`
    );

    // --- util 3) row(): monta <tr> do produto
    const row = p => {
      const preco = p.preco?.toFixed?.(2) ?? '0.00';
      const promo = p.preco_promocional?.toFixed?.(2) ?? '-';
      const imgSrc = p.imagem ? `data:image/jpeg;base64,${p.imagem}` : ph(p.nome);

      return `
        <tr>
          <td>${esc(p.id)}</td>
          <td><img src="${esc(imgSrc)}" alt="${esc(p.nome)}"
                   class="img-thumbnail" style="width:60px;height:60px;object-fit:cover;"></td>
          <td>${esc(p.nome)}</td>
          <td>${esc(p.marca || '-')}</td>
          <td>${esc(p.categoria || '-')}</td>
          <td class="text-end">${esc(p.quantidade)}</td>
          <td class="text-end">R$ ${esc(preco)}</td>
          <td class="text-end">${promo !== '-' ? "R$ " + esc(promo) : '-'}</td>
          <td>${esc(p.codigo)}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary" onclick="editarProduto(${esc(p.id)})">Editar</button>
              <button class="btn btn-outline-danger" onclick="excluirProduto(${esc(p.id)})">Excluir</button>
            </div>
          </td>
        </tr>`;
    };

    // --- fetch e renderização
    fetch(url, { cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || 'Erro ao listar produtos');
        tbody.innerHTML = d.produtos?.length
          ? d.produtos.map(row).join('')
          : `<tr><td colspan="10" class="text-center text-muted">Nenhum produto cadastrado.</td></tr>`;
      })
      .catch(err => {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
      });
  });
}




listarMarcas("#tabelaMarcas");
listarCategorias("#pCategoria");
listarCategorias("#prodCat");
listarNomeMarcas("#marcascdd");
listarProdutos("listprodutos");



