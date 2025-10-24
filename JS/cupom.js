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


// ==================== LISTAR, EDITAR E EXCLUIR CUPONS ====================
function iniciarCupons(idTabela, formSelector) {
  const tbody = document.getElementById(idTabela);
  const form = document.querySelector(formSelector);

  const esc = (s) => String(s || "").replace(/[&<>"']/g, c => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  }[c] || c));

  const moeda = new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" });

  const formataData = d => {
    if (!d) return "-";
    const data = new Date(d);
    return data.toLocaleDateString("pt-BR");
  };

  const linha = c => `
    <tr>
      <td>${esc(c.nome)}</td>
      <td>${moeda.format(c.valor)}</td>
      <td>${formataData(c.data_validade)}</td>
      <td>${esc(c.quantidade)}</td>
      <td class="text-end">
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary btn-editar" data-id="${c.id}"><i class="bi bi-pencil"></i> Editar</button>
          <button class="btn btn-outline-danger btn-excluir" data-id="${c.id}"><i class="bi bi-trash"></i> Excluir</button>
        </div>
      </td>
    </tr>`;

  // ==================== FUNÇÃO PARA LISTAR ====================
  const listar = () => {
    fetch("../PHP/cadastro_cupom.php?listar=1&format=json", { cache: "no-store" })
      .then(r => { if (!r.ok) throw new Error("Erro na conexão"); return r.json(); })
      .then(d => {
        if (!d.ok) throw new Error(d.error || "Erro ao listar cupons");
        const cupons = d.cupons || [];
        tbody.innerHTML = cupons.length
          ? cupons.map(linha).join("")
          : `<tr><td colspan="5" class="text-center text-muted">Nenhum cupom cadastrado.</td></tr>`;

        // Adiciona eventos aos botões
        tbody.querySelectorAll(".btn-editar").forEach(btn => {
          btn.onclick = () => carregarEdicao(btn.dataset.id);
        });
        tbody.querySelectorAll(".btn-excluir").forEach(btn => {
          btn.onclick = () => excluirCupom(btn.dataset.id);
        });
      })
      .catch(err => {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
      });
  };

  // ==================== FUNÇÃO PARA CARREGAR CUPOM NO FORMULÁRIO ====================
  const carregarEdicao = async (id) => {
    try {
      const resp = await fetch(`../PHP/cadastro_cupom.php?listar=1&id=${id}&format=json`, { cache: "no-store" });
      const data = await resp.json();
      if (!data.ok) throw new Error(data.error || "Erro ao carregar cupom");

      const c = data.cupons[0];
      if (!c) throw new Error("Cupom não encontrado");

      // Preenche o formulário
      form.querySelector('[name="id"]').value = c.id;
      form.querySelector('[name="nome"]').value = c.nome;
      form.querySelector('[name="valor"]').value = c.valor;
      form.querySelector('[name="data_validade"]').value = c.data_validade;
      form.querySelector('[name="quantidade"]').value = c.quantidade;

      form.querySelector('[name="acao"]').value = "atualizar";
      form.querySelector('button[type="submit"]').textContent = "Atualizar";
    } catch (err) {
      alert("Erro ao carregar cupom: " + err.message);
    }
  };

  // ==================== FUNÇÃO PARA EXCLUIR ====================
  const excluirCupom = (id) => {
    if (!confirm("Deseja realmente excluir este cupom?")) return;

    const formData = new FormData();
    formData.append("id", id);
    formData.append("acao", "excluir");

    fetch("../PHP/cadastro_cupom.php", { method: "POST", body: formData })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || "Erro ao excluir cupom");
        listar(); // atualiza tabela
      })
      .catch(err => alert("Erro ao excluir cupom: " + err.message));
  };

  // ==================== ENVIO DO FORMULÁRIO ====================
  form.onsubmit = async (e) => {
    e.preventDefault();
    const formData = new FormData(form);

    fetch("../PHP/cadastro_cupom.php", { method: "POST", body: formData })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || "Erro ao salvar cupom");
        form.reset();
        form.querySelector('[name="acao"]').value = "cadastrar";
        form.querySelector('button[type="submit"]').textContent = "Cadastrar";
        listar();
      })
      .catch(err => alert("Erro: " + err.message));
  };

  // Lista ao carregar
  listar();
}

// Iniciar
document.addEventListener("DOMContentLoaded", () => {
  iniciarCupons("listcupom", "form");
});


listarCupons("listcupom")