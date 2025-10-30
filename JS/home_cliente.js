/// ===================== CARROSSEL DE BANNERS (corrigido p/ PHP atual) ===================== //
(function () {
  const esc = s => (s ?? "").toString().replace(/[&<>"']/g, c => (
    {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]
  ));

  const placeholder = (w = 1200, h = 400, txt = "SEM IMAGEM") =>
    "data:image/svg+xml;base64," + btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}">
        <rect width="100%" height="100%" fill="#e9ecef"/>
        <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
              font-family="Arial, sans-serif" font-size="28" fill="#6c757d">${txt}</text>
      </svg>`
    );

  // Monta o src da imagem
  function resolveImagemSrc(b) {
    if (!b || !b.imagem) return placeholder();

    const img = b.imagem.trim();

    // Se já vier como data URL
    if (img.startsWith("data:")) return img;

    // Se for base64 puro
    if (/^[A-Za-z0-9+/=\s]+$/.test(img.replace(/\s+/g, ""))) {
      return `data:image/jpeg;base64,${img}`;
    }

    // Se for caminho de arquivo
    if (/^(https?:)?\/\//.test(img) || img.startsWith("/")) {
      return img;
    }

    return placeholder();
  }

  function renderErro(container, titulo, detalhesHtml) {
    container.innerHTML = `
      <div class="carousel-item active">
        <div class="p-3">
          <div class="alert alert-danger mb-2"><strong>${esc(titulo)}</strong></div>
          <div class="alert alert-light border small" style="white-space:pre-wrap">${esc(detalhesHtml)}</div>
        </div>
      </div>`;
    const ind = document.getElementById("banners-indicators");
    if (ind) ind.innerHTML = "";
  }

  function renderCarrossel(container, indicators, banners) {
    if (!Array.isArray(banners) || !banners.length) {
      renderErro(container, "Nenhum banner disponível.", "A lista retornou vazia.");
      return;
    }

    const itemsHtml = banners.map((b, i) => {
      const active = i === 0 ? "active" : "";
      const src = resolveImagemSrc(b);
      const desc = esc(b.descricao ?? "Banner");
      const link = b.link ? String(b.link) : null;

      const imgTag = `<img src="${src}" class="d-block w-100" alt="${desc}" loading="lazy" style="object-fit:cover; height:400px;">`;
      const wrapped = link
        ? `<a href="${esc(link)}" target="_blank" rel="noopener noreferrer">${imgTag}</a>`
        : imgTag;

      return `<div class="carousel-item ${active}">${wrapped}</div>`;
    }).join("");

    const indicatorsHtml = banners.map((_, i) =>
      `<button type="button" data-bs-target="#carouselBanners" data-bs-slide-to="${i}" class="${i===0?"active":""}" aria-label="Slide ${i+1}"></button>`
    ).join("");

    container.innerHTML = itemsHtml;
    if (indicators) indicators.innerHTML = indicatorsHtml;
  }

  async function fetchWithTimeout(resource, options = {}) {
    const { timeout = 10000 } = options;
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);
    try {
      return await fetch(resource, {
        ...options,
        signal: controller.signal,
        headers: { "Accept": "application/json" }
      });
    } finally {
      clearTimeout(id);
    }
  }

  async function tentarCaminhos(urls) {
    for (const url of urls) {
      try {
        const r = await fetchWithTimeout(url, { timeout: 12000 });
        const contentType = r.headers.get("content-type") || "";
        const raw = await r.text();

        let data = null;
        if (/application\/json/i.test(contentType) || raw.trim().startsWith("{") || raw.trim().startsWith("[")) {
          try { data = JSON.parse(raw); } catch {}
        }

        if (r.ok && data && Array.isArray(data.banners)) {
          return { ok: true, url, data };
        }
      } catch {
        // continua testando os próximos caminhos
      }
    }
    return { ok: false };
  }

  async function listarBannersCarrossel({
    containerSelector = "#banners-home",
    indicatorsSelector = "#banners-indicators",
    urlCandidates = [
      "../PHP/banners.php?listar=1",
      "PHP/banners.php?listar=1",
      "../../PHP/banners.php?listar=1"
    ]
  } = {}) {
    const container = document.querySelector(containerSelector);
    const indicators = document.querySelector(indicatorsSelector);
    if (!container) return;

    container.innerHTML = `<div class="carousel-item active"><div class="p-3 text-muted">Carregando banners…</div></div>`;
    if (indicators) indicators.innerHTML = "";

    const tentativa = await tentarCaminhos(urlCandidates);
    if (!tentativa.ok) {
      renderErro(container, "Erro ao carregar banners.",
        "• Verifique o caminho do PHP (?listar=1)\n• O PHP deve retornar { ok:true, banners:[...] }");
      return;
    }

    // Remove apenas o filtro de validade (mostra todos)
    const lista = tentativa.data.banners.slice();

    renderCarrossel(container, indicators, lista);
  }

  document.addEventListener("DOMContentLoaded", () => {
    listarBannersCarrossel({
      urlCandidates: ["../PHP/banners.php?listar=1"],
    });
  });
})();




// ====== CATEGORIAS (chips) + PRODUTOS (cards) com filtro no BACKEND ====== //
(function () {
  const $ = sel => document.querySelector(sel);
  const esc = s => (s ?? "").toString().replace(/[&<>"']/g, c =>
    ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c] || c)
  );
  const moneyBR = v => isFinite(v) ? v.toLocaleString('pt-BR', { style:'currency', currency:'BRL' }) : "";

  const placeholder = (w = 600, h = 400, txt = "SEM IMAGEM") =>
    "data:image/svg+xml;base64," + btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}">
        <rect width="100%" height="100%" fill="#f2f2f2"/>
        <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
              font-family="Arial, sans-serif" font-size="18" fill="#6c757d">${txt}</text>
      </svg>`
    );

  function resolveImg(prod) {
    const s = (prod?.imagem ?? "").trim();
    if (!s) return placeholder();
    if (s.startsWith("data:")) return s;
    if (/^(https?:)?\/\//i.test(s) || s.startsWith("/")) return s;
    if (/^[A-Za-z0-9+/=\s]+$/.test(s.replace(/\s+/g, ""))) return `data:image/jpeg;base64,${s}`;
    return placeholder();
  }

  function produtoCard(prod) {
    const src  = resolveImg(prod);
    const nome = esc(prod?.nome ?? "Produto");
    const alt  = esc(prod?.texto_alternativo ?? nome);
    const marca= esc(prod?.marca ?? "");
    const cat  = esc(prod?.categoria ?? "");

    const temPromo   = prod?.preco_promocional && Number(prod.preco_promocional) > 0;
    const precoNorm  = isFinite(prod?.preco) ? moneyBR(Number(prod.preco)) : "";
    const precoPromo = temPromo ? moneyBR(Number(prod.preco_promocional)) : null;

    return `
      <div class="col">
        <div class="card h-100 shadow-sm">
          <img src="${src}" class="card-img-top" alt="${alt}" loading="lazy" style="object-fit:cover; aspect-ratio: 4/3;">
          <div class="card-body d-flex flex-column">
            <h6 class="card-title mb-1 text-truncate" title="${nome}">${nome}</h6>
            <div class="text-muted small mb-2">${marca ? `Marca: ${marca}` : ""} ${cat ? `• ${cat}` : ""}</div>
            <div class="mb-2">
              ${
                temPromo
                  ? `<div class="fw-bold">${precoPromo} <span class="text-decoration-line-through text-muted ms-2">${precoNorm}</span></div>`
                  : `<div class="fw-bold">${precoNorm}</div>`
              }
            </div>
            <div class="mt-auto d-grid gap-2">
              <button class="btn btn-primary btn-sm" data-id="${prod.id}">Adicionar ao carrinho</button>
              <button class="btn btn-outline-secondary btn-sm" data-id="${prod.id}">Detalhes</button>
            </div>
          </div>
        </div>
      </div>`;
  }

  const URLS = {
    categorias: "PHP/cadastro_categorias.php?listar=1&format=json",
    produtosAll: "PHP/cadastro_produtos.php?listar=1",
    produtosByCat: id => `PHP/cadastro_produtos.php?listar_por_categoria=1&idCategoria=${encodeURIComponent(id)}`
  };

  const state = { categorias:[], catMap:new Map(), activeCat:"", produtos:[] };

  function normalizeCategorias(payload) {
    const arr = Array.isArray(payload?.categorias) ? payload.categorias : Array.isArray(payload) ? payload : [];
    return arr.map(c => ({
      id: Number(c.id ?? c.idCategoria_produtos ?? 0),
      nome: String(c.nome ?? "Categoria")
    })).filter(c => c.id);
  }

  function normalizeProdutos(payload) {
    const arr = Array.isArray(payload?.produtos) ? payload.produtos : Array.isArray(payload) ? payload : [];
    return arr.map(p => ({
      id: Number(p.idProdutos ?? 0),
      nome: String(p.nome ?? "Produto"),
      preco: Number(p.preco ?? 0),
      preco_promocional: p.preco_promocional ? Number(p.preco_promocional) : null,
      categoria: String(p.categoria ?? p.categoria_nome ?? ""),
      marca: String(p.marca ?? ""),
      imagem: p.imagem ?? null,
      texto_alternativo: p.texto_alternativo ?? p.nome ?? "Produto"
    }));
  }

  function buildChip({ id, nome }) {
    const isActive = String(id) === String(state.activeCat);
    const base = "btn btn-sm rounded-pill px-3";
    const cls  = isActive ? `btn-primary ${base}` : `btn-outline-primary ${base}`;
    return `<button type="button" class="${cls}" data-cat="${id}" title="${esc(nome)}">${esc(nome)}</button>`;
  }

  function renderChips() {
    const wrap = $("#cats-chips");
    if (!wrap) return;
    const chips = [`<button type="button" class="${state.activeCat==="" ? "btn btn-primary" : "btn btn-outline-primary"} btn-sm rounded-pill px-3" data-cat="">Todas as categorias</button>`]
                  .concat(state.categorias.map(buildChip));
    wrap.innerHTML = chips.join("");
  }

  function setActiveChip(catId) { 
    state.activeCat = String(catId ?? ""); 
    renderChips();
    const sel = $("#filtro-categoria");
    if(sel) sel.value = state.activeCat;
  }

  async function carregarCategorias() {
    try {
      const r = await fetch(URLS.categorias, { headers: { "Accept":"application/json" } });
      const data = await r.json();
      const lista = normalizeCategorias(data);
      if (lista.length) { 
        state.categorias = lista; 
        state.catMap = new Map(lista.map(c=>[c.id,c.nome])); 
      }
      renderChips();
    } catch(err) {
      console.error("Erro ao carregar categorias:", err);
    }
  }

  async function carregarProdutos(idCat="") {
    const status = $("#produtos-status");
    const grid   = $("#produtos-grid");
    status && (status.textContent = "Carregando produtos…");
    grid && (grid.innerHTML = "");

    const url = idCat ? URLS.produtosByCat(idCat) : URLS.produtosAll;

    try {
      const r = await fetch(url, { headers: { "Accept":"application/json" } });
      const data = await r.json();

      if (!data.ok) throw new Error("Resposta do backend com ok=false");

      const lista = normalizeProdutos(data.produtos ?? []);
      state.produtos = lista;

      if (!lista.length) {
        grid.innerHTML = "";
        status && (status.innerHTML = `<div class="alert alert-warning">Nenhum produto encontrado.</div>`);
        return;
      }

      grid.innerHTML = lista.map(produtoCard).join("");
      status && (status.textContent = "");
    } catch(err) {
      console.error(err);
      grid.innerHTML = "";
      status && (status.innerHTML = `<div class="alert alert-danger">Não foi possível carregar os produtos.</div>`);
    }
  }

  function wireEvents() {
    $("#cats-chips")?.addEventListener("click", e => {
      const btn = e.target.closest("button[data-cat]");
      if (!btn) return;
      const catId = btn.getAttribute("data-cat") ?? "";
      setActiveChip(catId);
      carregarProdutos(catId);
    });

    $("#filtro-categoria")?.addEventListener("change", e => {
      const catId = e.target.value ?? "";
      setActiveChip(catId);
      carregarProdutos(catId);
    });
  }

  document.addEventListener("DOMContentLoaded", async () => {
    state.activeCat = "";
    await carregarCategorias();
    await carregarProdutos();
    wireEvents();
  });
})();
