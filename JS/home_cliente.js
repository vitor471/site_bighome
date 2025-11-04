/* =========================================================================
   HELPERS GERAIS
   ========================================================================= */
const $  = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const esc = s => (s ?? "").toString().replace(/[&<>"']/g, c => (
  {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]
));
const money = v => isFinite(v) ? Number(v).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}) : "";

// Tenta decodificar texto como JSON; se falhar, retorna null
function tryJSON(txt){
  try { return JSON.parse(txt); } catch { return null; }
}

// Faz fetch com timeout + tenta interpretar JSON mesmo sem header correto
async function smartFetch(url, {timeout = 12000, accept = "application/json"} = {}) {
  const controller = new AbortController();
  const t = setTimeout(() => controller.abort(), timeout);
  try {
    const res = await fetch(url, { headers: { "Accept": accept }, signal: controller.signal });
    const ct = (res.headers.get("content-type") || "").toLowerCase();
    const raw = await res.text();
    const asJson = (/json/.test(ct) || raw.trim().startsWith("{") || raw.trim().startsWith("[")) ? tryJSON(raw) : null;
    return { ok: res.ok, status: res.status, data: asJson, raw };
  } finally {
    clearTimeout(t);
  }
}

// Placeholder SVG (para banners/produtos)
const placeholder = (w=1200, h=400, txt="SEM IMAGEM") =>
  "data:image/svg+xml;base64," + btoa(
    `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}">
      <rect width="100%" height="100%" fill="#e9ecef"/>
      <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
            font-family="Arial, sans-serif" font-size="28" fill="#6c757d">${txt}</text>
    </svg>`
  );

// Normaliza qualquer campo de imagem
function resolveBase64OrPath(img, {w=1200,h=400,txt="SEM IMAGEM"}={}) {
  if (!img) return placeholder(w,h,txt);
  const s = String(img).trim();
  if (!s) return placeholder(w,h,txt);
  if (s.startsWith("data:")) return s;                               // já é data URL
  if (/^[A-Za-z0-9+/=\s]+$/.test(s.replace(/\s+/g,"")))              // base64 “cru”
    return `data:image/jpeg;base64,${s}`;
  if (/^(https?:)?\/\//.test(s) || s.startsWith("/")) return s;      // caminho/URL
  return placeholder(w,h,txt);
}

/* =========================================================================
   CARROSSEL DE BANNERS
   - Aceita retorno { ok:true, banners:[ {imagem, descricao, link?...} ] }
   - Tenta caminhos relativos comuns (index em /, /PAGINAS_..., etc.)
   ========================================================================= */
(async function bannersCarrossel(){
  const container   = $("#banners-home");
  const indicators  = $("#banners-indicators");
  if (!container) return;

  // Estado inicial
  container.innerHTML = `<div class="carousel-item active"><div class="p-3 text-muted">Carregando banners…</div></div>`;
  if (indicators) indicators.innerHTML = "";

  const candidates = [
    "PHP/banners.php?listar=1",
    "../PHP/banners.php?listar=1",
    "../../PHP/banners.php?listar=1"
  ];

  let payload = null;
  let usedUrl = null;
  for (const url of candidates) {
    const r = await smartFetch(url);
    if (r.ok && r.data && Array.isArray(r.data.banners)) { payload = r.data; usedUrl = url; break; }
  }

  if (!payload) {
    container.innerHTML = `
      <div class="carousel-item active">
        <div class="p-3">
          <div class="alert alert-danger mb-2"><strong>Erro ao carregar banners.</strong></div>
          <div class="alert alert-light border small">Verifique o caminho do PHP (banners.php?listar=1) e o retorno JSON.</div>
        </div>
      </div>`;
    if (indicators) indicators.innerHTML = "";
    return;
  }

  const banners = Array.isArray(payload.banners) ? payload.banners : [];
  if (!banners.length) {
    container.innerHTML = `<div class="carousel-item active"><div class="p-3 text-muted">Nenhum banner disponível.</div></div>`;
    if (indicators) indicators.innerHTML = "";
    return;
  }

  const itemsHtml = banners.map((b, i) => {
    const active = i === 0 ? "active" : "";
    const src  = resolveBase64OrPath(b.imagem, {w:1200,h:400});
    const desc = esc(b.descricao ?? "Banner");
    const link = b.link ? String(b.link) : null;
    const tagImg = `<img src="${src}" class="d-block w-100" alt="${desc}" loading="lazy" style="object-fit:cover; height:400px;">`;
    const content = link ? `<a href="${esc(link)}" target="_blank" rel="noopener noreferrer">${tagImg}</a>` : tagImg;
    return `<div class="carousel-item ${active}">${content}</div>`;
  }).join("");

  const indsHtml = banners.map((_, i) =>
    `<button type="button" data-bs-target="#carouselBanners" data-bs-slide-to="${i}" class="${i===0?"active":""}" aria-label="Slide ${i+1}"></button>`
  ).join("");

  container.innerHTML = itemsHtml;
  if (indicators) indicators.innerHTML = indsHtml;
})();

/* =========================================================================
   CATEGORIAS + PRODUTOS
   - Categorias: cadastro_categorias.php?listar=1&format=json
     → { ok:true, categorias:[{id, nome}] }
   - Produtos (geral): cadastro_produtos.php (GET)
     → { ok:true, produtos:[{...}] }
   - Produtos por categoria: cadastro_produtos.php?listar_por_categoria=1&idCategoria=NN
     → { ok:true, produtos:[{...}] }
   ========================================================================= */
(function produtosECategorias(){
  const chipsContainer  = $("#cats-chips");
  const selectCategoria = $("#filtro-categoria");
  const grid            = $("#produtos-grid");
  const status          = $("#produtos-status");

  // Monta o card do produto
  function cardProduto(p){
    const src   = resolveBase64OrPath(p.imagem, {w:400,h:300,txt:"SEM IMAGEM"});
    const nome  = esc(p.nome ?? "Produto");
    const marca = p.marca ? esc(p.marca) : "";
    const cat   = p.categoria ? esc(p.categoria) : "";
    const preco = money(p.preco ?? 0);
    const promo = p.preco_promocional != null ? money(p.preco_promocional) : null;

    return `
      <div class="col">
        <div class="card h-100 shadow-sm">
          <img src="${src}" class="card-img-top" alt="${nome}" style="object-fit:cover; aspect-ratio:4/3;">
          <div class="card-body d-flex flex-column">
            <h6 class="card-title mb-1 text-truncate" title="${nome}">${nome}</h6>
            <div class="text-muted small mb-2">${marca}${(marca && cat) ? " • " : ""}${cat}</div>
            ${
              promo
                ? `<div><strong>${promo}</strong> <span class="text-decoration-line-through text-muted">${preco}</span></div>`
                : `<div><strong>${preco}</strong></div>`
            }
            <div class="mt-auto d-grid gap-2 mt-2">
              <button class="btn btn-primary btn-sm">Adicionar</button>
              <button class="btn btn-outline-secondary btn-sm">Detalhes</button>
            </div>
          </div>
        </div>
      </div>`;
  }

  // Carrega categorias (tenta caminhos comuns)
  async function carregarCategorias(){
    const paths = [
      "PHP/cadastro_categorias.php?listar=1&format=json",
      "../PHP/cadastro_categorias.php?listar=1&format=json",
      "../../PHP/cadastro_categorias.php?listar=1&format=json",
    ];

    let data = null;
    for (const url of paths) {
      const r = await smartFetch(url);
      if (r.ok && r.data && r.data.ok && Array.isArray(r.data.categorias)) { data = r.data; break; }
    }

    const lista = data?.categorias ?? [];

    if (chipsContainer) {
      chipsContainer.innerHTML =
        `<button class="btn btn-primary btn-sm rounded-pill px-3" data-cat="">Todas</button>` +
        lista.map(c => `<button class="btn btn-outline-primary btn-sm rounded-pill px-3" data-cat="${c.id}">${esc(c.nome)}</button>`).join("");
    }

    if (selectCategoria) {
      selectCategoria.innerHTML =
        `<option value="">Todas</option>` +
        lista.map(c => `<option value="${c.id}">${esc(c.nome)}</option>`).join("");
    }
  }

  // Carrega produtos (geral ou por categoria)
  async function carregarProdutos(catId = ""){
    if (!grid || !status) return;
    grid.innerHTML = "";
    status.textContent = "Carregando produtos…";

    let urlCandidates = [];
    if (catId) {
      urlCandidates = [
        `PHP/cadastro_produtos.php?listar_por_categoria=1&idCategoria=${encodeURIComponent(catId)}`,
        `../PHP/cadastro_produtos.php?listar_por_categoria=1&idCategoria=${encodeURIComponent(catId)}`,
        `../../PHP/cadastro_produtos.php?listar_por_categoria=1&idCategoria=${encodeURIComponent(catId)}`
      ];
    } else {
      urlCandidates = [
        "PHP/cadastro_produtos.php",
        "../PHP/cadastro_produtos.php",
        "../../PHP/cadastro_produtos.php"
      ];
    }

    let payload = null;
    for (const url of urlCandidates) {
      const r = await smartFetch(url);
      if (r.ok && r.data && r.data.ok && Array.isArray(r.data.produtos)) { payload = r.data; break; }
    }

    if (!payload) {
      status.innerHTML = `<div class="alert alert-danger mt-3 mb-0">Erro ao carregar produtos.</div>`;
      return;
    }

    const produtos = payload.produtos || [];
    if (!produtos.length) {
      status.innerHTML = `<div class="alert alert-warning mt-3 mb-0">Nenhum produto encontrado.</div>`;
      return;
    }

    grid.innerHTML = produtos.map(cardProduto).join("");
    status.textContent = "";
  }

  // Eventos
  document.addEventListener("DOMContentLoaded", async () => {
    await carregarCategorias();
    await carregarProdutos();

    chipsContainer?.addEventListener("click", e => {
      const btn = e.target.closest("button[data-cat]");
      if (!btn) return;
      const id = btn.dataset.cat;

      // Atualiza estilos dos chips
      $$("#cats-chips button").forEach(b => {
        b.classList.remove("btn-primary");
        b.classList.remove("btn-outline-primary");
        b.classList.add("btn-outline-primary");
      });
      btn.classList.remove("btn-outline-primary");
      btn.classList.add("btn-primary");

      carregarProdutos(id);
      if (selectCategoria) selectCategoria.value = id ?? "";
    });

    selectCategoria?.addEventListener("change", e => {
      carregarProdutos(e.target.value || "");
      // Sincroniza chips
      if (chipsContainer) {
        const current = chipsContainer.querySelector(`button[data-cat="${e.target.value}"]`) ||
                        chipsContainer.querySelector(`button[data-cat=""]`);
        if (current) current.click();
      }
    });
  });
})();
