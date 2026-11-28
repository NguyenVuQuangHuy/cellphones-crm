"use strict";

const state = { products: [], category: "Tất cả", cart: loadCart() };
const portal = document.querySelector(".cps-portal");
const $ = (s, root = portal) => root.querySelector(s);
const $$ = (s, root = portal) => [...root.querySelectorAll(s)];
const money = (n) => new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 }).format(Number(n) || 0);
const escapeHtml = (value) => String(value ?? "").replace(/[&<>'"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;" }[c]));

function loadCart() {
  try { const data = JSON.parse(localStorage.getItem("cellphones-cart") || "[]"); return Array.isArray(data) ? data : []; }
  catch { return []; }
}
function saveCart() { localStorage.setItem("cellphones-cart", JSON.stringify(state.cart)); updateCartCount(); }
function updateCartCount() { $("#cart-count").textContent = state.cart.reduce((sum, item) => sum + item.qty, 0); }
function toast(text) { const el = $("#toast"); el.textContent = text; el.classList.add("show"); clearTimeout(toast.timer); toast.timer = setTimeout(() => el.classList.remove("show"), 2600); }

function showView(name, moveFocus = true) {
  $$(".view").forEach(v => v.classList.toggle("active", v.id === `view-${name}`));
  $$(".nav-link").forEach(button => {
    const active = button.dataset.view === name;
    button.classList.toggle("active", active);
    if (active) button.setAttribute("aria-current", "page");
    else button.removeAttribute("aria-current");
  });
  $("#main-nav").classList.remove("open"); $(".menu-toggle").setAttribute("aria-expanded", "false");
  if (name === "cart") renderCart();
  if (moveFocus) {
    const heading = $(`#view-${name} h1`);
    heading.tabIndex = -1;
    heading.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: "instant" });
  }
}

function showPanel(id) {
  $$(".subtab").forEach(button => {
    const active = button.dataset.panel === id;
    button.classList.toggle("active", active);
    button.setAttribute("aria-selected", String(active));
    button.tabIndex = active ? 0 : -1;
  });
  $$(".subpanel").forEach(panel => {
    panel.classList.toggle("active", panel.id === id);
    panel.setAttribute("role", "tabpanel");
    panel.setAttribute("aria-labelledby", `tab-${panel.id}`);
  });
}

async function api(action, data = {}) {
  const response = await fetch(window.CELLPHONES_API_URL || "api.php", { method: "POST", headers: { "Content-Type": "application/json; charset=utf-8" }, body: JSON.stringify({ action, data }) });
  let result; try { result = await response.json(); } catch { throw new Error("Máy chủ trả dữ liệu không hợp lệ."); }
  if (!response.ok || !result.ok) throw new Error(result.message || "Không thể hoàn tất yêu cầu.");
  return result;
}

async function loadProducts() {
  try {
    const result = await api("get_products"); state.products = result.data.items || [];
    const source = { mock_fallback: `Dữ liệu mô phỏng · ${state.products.length} sản phẩm`, cache: "Dữ liệu lưu từ Odoo · tối đa 5 phút", odoo_live: "Đồng bộ trực tiếp từ Odoo", fallback_on_error: "Odoo tạm gián đoạn · đang dùng dữ liệu dự phòng" }[result.data.source] || `${state.products.length} sản phẩm`;
    state.products = [...state.products].sort((a, b) => Number(b.qty_available > 0) - Number(a.qty_available > 0));
    $("#source-label").textContent = source; fillProductSelects(); renderFilters(); renderProducts();
  } catch (error) { $("#product-grid").innerHTML = `<div class="empty">${escapeHtml(error.message)}</div>`; $("#source-label").textContent = "Chưa tải được dữ liệu"; }
}

function fillProductSelects() {
  const options = state.products.map(p => `<option value="${p.id}">${escapeHtml(p.default_code)} · ${escapeHtml(p.name)}</option>`).join("");
  $("#consult-product").insertAdjacentHTML("beforeend", options); $("#ticket-product").insertAdjacentHTML("beforeend", options);
}
function renderFilters() {
  const order = ["Tất cả", "Điện thoại", "Laptop", "Tablet", "Đồng hồ thông minh", "Âm thanh", "Phụ kiện"];
  $("#category-filters").innerHTML = order.map(name => {
    const count = name === "Tất cả" ? state.products.length : state.products.filter(p => p.category === name).length;
    const active = name === state.category;
    return `<button class="filter-btn ${active ? "active" : ""}" aria-pressed="${active}" data-category="${escapeHtml(name)}">${escapeHtml(name)} <span class="filter-count">(${count})</span></button>`;
  }).join("");
}
function renderProducts() {
  if (!$("#category-filters").childElementCount) return;
  const term = $("#product-search").value.trim().toLocaleLowerCase("vi"); const stockOnly = $("#stock-only").checked;
  const list = state.products.filter(p => (state.category === "Tất cả" || p.category === state.category) && (!stockOnly || Number(p.qty_available) > 0) && (!term || `${p.name} ${p.default_code}`.toLocaleLowerCase("vi").includes(term)));
  $("#filter-summary").textContent = `${list.length} sản phẩm phù hợp.`;
  $("#product-grid").innerHTML = list.length ? list.map(p => {
    const quantity = Number(p.qty_available);
    const stockLabel = quantity > 0 ? `Sẵn hàng tại quầy (SL: ${quantity})` : "Đặt trước / Giao từ kho tổng";
    return `<article class="product-card">
      <h3>${escapeHtml(p.name)}</h3>
      <div class="meta"><span>${escapeHtml(p.category)}</span><span>${escapeHtml(p.default_code)}</span></div>
      <p class="desc">${escapeHtml(p.description_sale || "Thông tin bảo hành đang cập nhật.")}</p>
      <div class="price">${money(p.list_price)}</div>
      <div class="stock ${quantity > 0 ? "available" : ""}">${stockLabel}</div>
      <div class="card-actions"><button data-consult="${Number(p.id)}">Tư vấn</button><button class="add" data-add="${Number(p.id)}">Thêm vào giỏ</button></div>
    </article>`;
  }).join("") : '<div class="empty">Chưa có sản phẩm phù hợp. Hãy đổi từ khóa hoặc điều chỉnh bộ lọc.</div>';
}
function addToCart(id) {
  const product = state.products.find(p => Number(p.id) === Number(id)); if (!product) return;
  const found = state.cart.find(i => i.product_id === Number(id)); if (found) found.qty += 1; else state.cart.push({ product_id: Number(id), qty: 1, name: product.name, code: product.default_code, price: Number(product.list_price) });
  saveCart(); toast(`Đã thêm ${product.name} vào giỏ`);
}
function renderCart() {
  const box = $("#cart-items");
  box.innerHTML = state.cart.length ? state.cart.map(item => {
    const id = escapeHtml(item.product_id);
    const name = escapeHtml(item.name);
    return `<div class="cart-row">
      <div>
        <h3>${name}</h3>
        <p>${escapeHtml(item.code)}</p>
        <button class="remove" data-remove="${id}" aria-label="Xóa ${name} khỏi giỏ">Xóa</button>
      </div>
      <div class="qty" role="group" aria-label="Số lượng ${name}">
        <button data-qty="${id}" data-delta="-1" aria-label="Giảm số lượng ${name}">−</button>
        <span>${escapeHtml(item.qty)}</span>
        <button data-qty="${id}" data-delta="1" aria-label="Tăng số lượng ${name}">+</button>
      </div>
      <strong>${money(item.price * item.qty)}</strong>
    </div>`;
  }).join("") : '<div class="empty">Giỏ hàng đang trống. Hãy chọn sản phẩm trước khi đặt COD.</div>';
  const subtotal = state.cart.reduce((sum, i) => sum + i.price * i.qty, 0); $("#subtotal").textContent = money(subtotal); $("#vat").textContent = money(subtotal * .1); $("#estimate").textContent = money(subtotal * 1.1);
}

function normalizePhone(value) { let digits = String(value).replace(/\D/g, ""); if (digits.startsWith("84") && digits.length === 11) digits = `0${digits.slice(2)}`; return digits; }
function validateForm(form) {
  $$(".field-error", form).forEach(error => error.remove());
  $$(".invalid", form).forEach(field => {
    field.classList.remove("invalid");
    field.removeAttribute("aria-invalid");
    field.removeAttribute("aria-describedby");
  });
  const fields = $$("input, select, textarea", form);
  for (const field of fields) {
    const value = field.value.trim();
    let hint = "";
    if (field.required && !value) hint = "Vui lòng điền trường này.";
    else if (["phone", "partner_phone"].includes(field.name)) {
      const phone = normalizePhone(value);
      if (!/^0[35789][0-9]{8}$/.test(phone)) hint = "Nhập số di động Việt Nam hợp lệ.";
      else field.value = phone;
    } else if (field.type === "email" && !field.checkValidity()) {
      hint = "Nhập địa chỉ email hợp lệ.";
    }
    if (hint) {
      const error = document.createElement("span");
      error.className = "field-error";
      error.id = `${form.id}-${field.name}-error`;
      error.textContent = hint;
      field.after(error);
      field.classList.add("invalid");
      field.setAttribute("aria-invalid", "true");
      field.setAttribute("aria-describedby", error.id);
    }
  }
  const firstInvalid = $(".invalid", form);
  if (!firstInvalid) return true;
  message(form, "Vui lòng kiểm tra các trường được đánh dấu.", "error");
  firstInvalid.focus();
  return false;
}
function message(form, text, type = "success", copy = "") {
  const box = $(".form-message", form); box.className = `form-message show ${type}`; box.innerHTML = `${escapeHtml(text)}${copy ? ` <button type="button" class="copy-code" data-copy="${escapeHtml(copy)}">Sao chép mã</button>` : ""}`;
}
function formData(form) { return Object.fromEntries(new FormData(form).entries()); }
async function submit(form, action, transform, success) {
  const button = $(".submit-btn", form);
  if (button.disabled || !validateForm(form)) return;
  const label = button.textContent;
  button.disabled = true;
  button.setAttribute("aria-busy", "true");
  button.textContent = action === "check_ticket" ? "Đang tra cứu…" : "Đang gửi yêu cầu…";
  try { const result = await api(action, transform(formData(form))); success(result); }
  catch (error) { message(form, error.message, "error"); }
  finally {
    button.disabled = false;
    button.removeAttribute("aria-busy");
    button.textContent = label;
  }
}

function renderTicket(data) {
  const order = [1, 2, 11, 12, 13, 6], currentIndex = order.indexOf(Number(data.stage_id));
  const labels = { 1: "Mới tiếp nhận", 2: "Đang xác minh", 11: "Chờ khách / linh kiện", 12: "Đang xử lý", 13: "Đã giải quyết", 6: "Đóng" };
  const steps = order.map((id, index) => {
    const status = index === currentIndex ? "current" : index < currentIndex ? "done" : "";
    return `<li class="step ${status}" ${status === "current" ? 'aria-current="step"' : ""}>${labels[id]}</li>`;
  }).join("");
  const repairs = (data.repairs || []).map(repair => `<div class="repair">
    <strong>${escapeHtml(repair.name)}</strong>
    <span>${escapeHtml(repair.state_label)} · ${repair.under_warranty ? "Trong bảo hành" : "Ngoài bảo hành"}</span>
  </div>`).join("");
  const updated = data.updated_at ? ` · Cập nhật ${escapeHtml(data.updated_at)}` : "";
  return `<article class="ticket-card">
    <div class="ticket-top">
      <div>
        ${data.ticket_ref ? `<p class="ticket-ref">Phiếu #${escapeHtml(data.ticket_ref)}</p>` : ""}
        <h2>${escapeHtml(data.subject || data.device_name || "Thiết bị bảo hành")}</h2>
        <p>${escapeHtml(data.branch_label)}${updated}</p>
      </div>
      <span class="status-chip${data.sla_paused ? " paused" : ""}">${data.sla_paused ? "SLA đang tạm dừng" : escapeHtml(data.stage_name)}</span>
    </div>
    <ol class="stepper" aria-label="Tiến độ bảo hành">${steps}</ol>
    ${repairs ? `<div class="repairs"><h3>Lệnh sửa chữa kỹ thuật</h3>${repairs}</div>` : ""}
  </article>`;
}

portal.addEventListener("click", async (event) => {
  const nav = event.target.closest("[data-view]");
  if (nav) {
    if (nav.dataset.openPanel) showPanel(nav.dataset.openPanel);
    showView(nav.dataset.view);
  }
  const filter = event.target.closest("[data-category]");
  if (filter) {
    state.category = filter.dataset.category;
    $$(".filter-btn").forEach(button => {
      button.classList.toggle("active", button === filter);
      button.setAttribute("aria-pressed", String(button === filter));
    });
    renderProducts();
  }
  const add = event.target.closest("[data-add]"); if (add) addToCart(add.dataset.add);
  const consult = event.target.closest("[data-consult]"); if (consult) { $("#consult-product").value = consult.dataset.consult; showView("consult"); }
  const qty = event.target.closest("[data-qty]");
  if (qty) {
    const item = state.cart.find(i => i.product_id === Number(qty.dataset.qty));
    if (item) {
      item.qty = Math.max(1, item.qty + Number(qty.dataset.delta));
      saveCart();
      renderCart();
      $(`[data-qty="${item.product_id}"][data-delta="${qty.dataset.delta}"]`).focus({ preventScroll: true });
    }
  }
  const remove = event.target.closest("[data-remove]");
  if (remove) {
    const position = $$("[data-remove]").indexOf(remove);
    state.cart = state.cart.filter(i => i.product_id !== Number(remove.dataset.remove));
    saveCart();
    renderCart();
    const remaining = $$("[data-remove]");
    (remaining[Math.min(position, remaining.length - 1)] || $("#cart-items-title")).focus();
  }
  const sub = event.target.closest("[data-panel]"); if (sub) showPanel(sub.dataset.panel);
  const copy = event.target.closest("[data-copy]"); if (copy) { try { await navigator.clipboard.writeText(copy.dataset.copy); toast("Đã sao chép mã"); } catch { toast(`Mã: ${copy.dataset.copy}`); } }
});

$(".menu-toggle").addEventListener("click", e => { const open = $("#main-nav").classList.toggle("open"); e.currentTarget.setAttribute("aria-expanded", String(open)); });
$(".subtabs").addEventListener("keydown", event => {
  const tabs = $$(".subtab");
  const current = tabs.indexOf(event.target);
  if (current < 0 || !["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) return;
  event.preventDefault();
  const index = event.key === "Home" ? 0 : event.key === "End" ? tabs.length - 1
    : (current + (event.key === "ArrowRight" ? 1 : -1) + tabs.length) % tabs.length;
  showPanel(tabs[index].dataset.panel);
  tabs[index].focus();
});
$("#product-search").addEventListener("input", renderProducts); $("#stock-only").addEventListener("change", renderProducts);
$("#consult-product").addEventListener("change", e => { const p = state.products.find(p => String(p.id) === e.target.value); if (p && !$("#consult-form textarea").value) $("#consult-form textarea").value = `Tôi muốn được tư vấn về ${p.name}.`; });
$("#ticket-product").addEventListener("change", e => { const p = state.products.find(p => String(p.id) === e.target.value); if (p) $("#ticket-form [name=ten_thiet_bi]").value = p.name; });

$("#consult-form").addEventListener("submit", event => {
  event.preventDefault();
  const form = event.currentTarget;
  submit(form, "create_lead", data => ({
    ...data,
    product_id: Number(data.product_id) || null,
    product_name: state.products.find(p => String(p.id) === data.product_id)?.name || "Nhu cầu mua hàng"
  }), result => {
    const routed = result.data.routed_to === "helpdesk" || result.data.ticket_ref;
    const code = routed ? `#${result.data.ticket_ref}` : `OPP #${result.data.record_id}`;
    message(form, `${result.message} · ${code}`, "success", code);
    const { team_label, is_existing_customer, ai_used, ai_category, ai_summary, idempotent_hit } = result.data;
    const details = [];
    if (team_label) details.push(team_label);
    if (!routed && !idempotent_hit && typeof is_existing_customer === "boolean") {
      details.push(is_existing_customer ? "Đã gắn hồ sơ khách hàng hiện có." : "Đã tạo hồ sơ khách hàng mới.");
    }
    if (ai_used) details.push(`Phân loại Gemini · ${ai_category}${ai_summary ? ` — ${ai_summary}` : ""}`);
    if (details.length) {
      const summary = document.createElement("p");
      summary.className = "result-detail";
      summary.textContent = details.join(" · ");
      $(".form-message", form).append(summary);
    }
    form.reset();
  });
});
$("#order-form").addEventListener("submit", event => {
  event.preventDefault();
  const form = event.currentTarget;
  if (!state.cart.length) {
    message(form, "Giỏ hàng đang trống.", "error");
    return;
  }
  const items = state.cart.map(({ product_id, qty }) => ({ product_id, qty }));
  submit(form, "create_order", data => ({ ...data, items }), result => {
    message(form, `${result.message} Tổng báo giá: ${money(result.data.amount_total)}.`, "success", result.data.order_name);
    for (const item of state.cart) {
      item.qty -= items.find(sent => sent.product_id === item.product_id)?.qty || 0;
    }
    state.cart = state.cart.filter(item => item.qty > 0);
    saveCart();
    renderCart();
    form.reset();
  });
});
$("#ticket-form").addEventListener("submit", event => {
  event.preventDefault();
  const form = event.currentTarget;
  submit(form, "create_ticket", data => ({ ...data, product_id: Number(data.product_id) || null }), result => {
    message(form, result.message, "success", `#${result.data.ticket_ref}`);
    form.reset();
  });
});
$("#lookup-form").addEventListener("submit", event => {
  event.preventDefault();
  const form = event.currentTarget;
  if ($(".submit-btn", form).disabled) return;
  $("#ticket-result").replaceChildren();
  submit(form, "check_ticket", ({ phone }) => ({ phone }), result => {
    message(form, result.message, "success");
    $("#ticket-result").innerHTML = result.data.tickets.map(renderTicket).join("");
  });
});

showView("products", false);
showPanel("ticket-create");
updateCartCount();
loadProducts();
