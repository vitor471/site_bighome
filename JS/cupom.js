// ==================== LISTAR CUPONS ====================
function listarCupons(idTabela) {
  document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.getElementById(idTabela);
    const url = "../PHP/cadastro_cupom.php?listar=1&format=json";

   // Função para escapar HTML (segurança)
const esc = (s) => {
  return String(s || "").replace(/[&<>"']/g, (c) => {
    const mapa = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;"
    };
    return mapa[c] || c;
  });
};



    // Formata valor em moeda BRL
    const moeda = new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" });

    // Função para formatar data
    const formataData = d => {
      if (!d) return "-";
      const data = new Date(d);
      return data.toLocaleDateString("pt-BR");
    };

    // Função que gera uma linha da tabela
    const linha = c => `
      <tr>
        <td>${esc(c.nome)}</td>
        <td>${moeda.format(c.valor)}</td>
        <td>${formataData(c.data_validade)}</td>
        <td>${esc(c.quantidade)}</td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" data-id="${c.id}">
              <i class="bi bi-pencil"></i> Editar
            </button>
            <button class="btn btn-outline-danger" data-id="${c.id}">
              <i class="bi bi-trash"></i> Excluir
            </button>
          </div>
        </td>
      </tr>`;

    // Faz a requisição e popula a tabela
    fetch(url, { cache: "no-store" })
      .then(r => {
        if (!r.ok) throw new Error("Erro na conexão");
        return r.json();
      })
      .then(d => {
        if (!d.ok) throw new Error(d.error || "Erro ao listar cupons");
        const cupons = d.cupons || [];
        tbody.innerHTML = cupons.length
          ? cupons.map(linha).join("")
          : `<tr><td colspan="5" class="text-center text-muted">Nenhum cupom cadastrado.</td></tr>`;
      })
      .catch(err => {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
      });
  });
}

listarCupons("listcupom")