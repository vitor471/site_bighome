// ==================== LISTAR BANNERS ====================
function listarBanners(tabelaId) {
  document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById(tabelaId);
    const url = '../PHP/cadastro_banners.php?listar=1&format=json';

    // Função para escapar caracteres especiais
    const esc = s => (s || '').replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    // Função para montar cada linha da tabela
    const row = b => `
      <tr>
        <td><img src="data:image/png;base64,${b.imagem}" class="mini-banner rounded" style="width:100px; height:50px; object-fit:cover;"></td>
        <td>${esc(b.descricao || '-')}</td>
        <td>${esc(b.link || '-')}</td>
        <td>${b.categoria_id || '—'}</td>
        <td>${b.data_validade ? new Date(b.data_validade).toLocaleDateString('pt-BR') : '-'}</td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" data-id="${b.id}"><i class="bi bi-pencil"></i> Editar</button>
            <button class="btn btn-outline-danger" data-id="${b.id}"><i class="bi bi-trash"></i> Excluir</button>
          </div>
        </td>
      </tr>
    `;

    // Faz a requisição e preenche a tabela
    fetch(url, { cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || 'Erro ao listar banners');
        const banners = d.banners || [];
        tbody.innerHTML = banners.length
          ? banners.map(row).join('')
          : `<tr><td colspan="6" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;
      })
      .catch(err => {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
      });
  });
}
