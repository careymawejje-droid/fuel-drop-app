// ===== FuelDrop shared script (loaded on every page) =====
// Sample data — replace with real data from your backend
// Fuel prices per litre (UGX), display names and the flat delivery fee
const PRICES = {petrol:5200, diesel:4900, paraffin:4300};
const NAMES  = {petrol:"Petrol", diesel:"Diesel", paraffin:"Paraffin"};
const FEE = 5000, KEY = "fueldrop";

// Default order values. Anything saved in localStorage overrides these.
const D = {fuel:"petrol", qty:40, vehicle:"Toyota Hilux – UBA 123K", addr:"",
  time:"ASAP (60–90 min)", pay:"momo", ordNo:"FD-1042", eta:25, step:2};
// s = the current order. Loaded from localStorage so it survives page changes.
let s = {...D};
try { s = {...D, ...JSON.parse(localStorage.getItem(KEY) || "{}")}; } catch(e) {}
// Save the current order back to localStorage
const save = () => { try { localStorage.setItem(KEY, JSON.stringify(s)); } catch(e) {} };

// Short helpers: $ = first match, $$ = all matches
const $ = q => document.querySelector(q);
const $$ = q => [...document.querySelectorAll(q)];
// Format a number as money, e.g. UGX 213,000
const money = n => "UGX " + n.toLocaleString("en-UG");
// Which page this is (from <body data-page="...">). Decides which block below runs.
const page = document.body.dataset.page;

// Fill every [data-bind] and [data-price] element in the page with current values,
// then update the tracking steps and the dashboard progress bar.
function bind(){
  const amt = PRICES[s.fuel] * s.qty;
  const v = {line:`${NAMES[s.fuel]} × ${s.qty} L`, amount:money(amt), fee:money(FEE),
    total:money(amt + FEE), vehicle:s.vehicle, addr:s.addr || "No address set",
    time:s.time, ordNo:s.ordNo, eta:s.eta, fuelQty:`${NAMES[s.fuel]} · ${s.qty} L`,
    trTitle:s.step >= 3 ? "Delivered" : "Driver is on the way"};
  $$("[data-bind]").forEach(e => e.textContent = v[e.dataset.bind]);
  $$("[data-price]").forEach(e => e.textContent = PRICES[e.dataset.price].toLocaleString());
  $$("#steps>div").forEach((d,i) => d.classList.toggle("done", i <= s.step));
  if($("#bar")) $("#bar").style.width = ((s.step + 1) / 4 * 100) + "%";
}

/* ---------- Order step 1 (orders/create.html) ----------
   Restore saved choices, update the summary as the user types, validate, go to payment. */
if(page === "order"){
  const f = $("#orderForm");
  f.fuel.value = s.fuel; f.qty.value = s.qty; f.vehicle.value = s.vehicle; f.addr.value = s.addr;
  $$("[name=time]").forEach(r => r.checked = r.value === s.time);
  // Any input change: update the order state, save it and refresh the summary
  f.addEventListener("input", e => {
    const n = e.target.name;
    if(n === "qty") s.qty = Math.max(0, +e.target.value || 0);
    else if(["fuel","time","vehicle","addr"].includes(n)) s[n] = e.target.value;
    save(); bind();
  });
  // Continue to payment (checks minimum litres and that an address was entered)
  f.addEventListener("submit", e => {
    e.preventDefault();
    if(s.qty < 5) return alert("Minimum order is 5 litres.");
    if(!s.addr.trim()) return $("#addr").focus();
    location.href = "payment.html";
  });
}

/* ---------- Order step 2 (orders/payment.html) ----------
   Choose payment method, then place the order. */
if(page === "payment"){
  if(!s.addr) location.href = "create.html";
  const f = $("#payForm");
  // Show the mobile money number box only when Mobile money is chosen
  const showMomo = () => $("#momoBox").classList.toggle("hidden", s.pay !== "momo");
  $$("[name=pay]").forEach(r => r.checked = r.value === s.pay);
  f.addEventListener("input", e => { if(e.target.name === "pay"){ s.pay = e.target.value; save(); showMomo(); } });
  showMomo();
  // Place order: make a sample order number, reset tracking, go to confirmation
  f.addEventListener("submit", e => {
    e.preventDefault();
    if(s.pay === "momo" && !f.phone.value.trim()) return f.phone.focus();
    s.ordNo = "FD-" + (1043 + Math.floor(Math.random() * 900));
    s.eta = 75; s.step = 0; save();
    location.href = "confirmation.html";
  });
}

/* ---------- Dashboard: Reorder button ---------- */
if($("#reorder")) $("#reorder").addEventListener("click", () => {
  s.fuel = "diesel"; s.qty = 60; save(); location.href = "orders/create.html";
});

/* Tracking (simulated driver on a real OpenStreetMap map) */
if(page === "track"){
  // Map points: dest = customer location, from = where the driver starts
  const dest = [s.lat ?? 0.3476, s.lng ?? 32.5825];          // customer (Kampala fallback)
  const from = [dest[0] + 0.04, dest[1] - 0.05];              // driver start
  const map = L.map("map").setView(dest, 13);
  L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19, attribution: "&copy; OpenStreetMap contributors"
  }).addTo(map);
  L.marker(dest).addTo(map).bindPopup("Delivery address");
  const truck = L.marker(from).addTo(map).bindPopup("Driver");
  L.polyline([from, dest], {color:"#f5a623", dashArray:"6"}).addTo(map);
  map.fitBounds([from, dest], {padding:[40, 40]});

  // Simulation: every 1.5 s the driver moves 4% closer, the ETA drops and the steps advance
  const start = s.eta; let p = 0;
  const t = setInterval(() => {
    p = Math.min(p + 4, 100);
    truck.setLatLng([from[0] + (dest[0] - from[0]) * p / 100, from[1] + (dest[1] - from[1]) * p / 100]);
    s.step = p < 20 ? 1 : p < 100 ? 2 : 3;
    s.eta = Math.max(0, Math.round(start * (1 - p / 100)));
    save(); bind();
    if(p >= 100) clearInterval(t);
  }, 1500);
  $("#cancel").addEventListener("click", () => {
    if(confirm("Cancel this order?")){ clearInterval(t); location.href = "../dashboard.html"; }
  });
}

// Run once when the page loads: fill in the values
// (the icons are drawn by the inline lucide script at the bottom of each page)
bind();
