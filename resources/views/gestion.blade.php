
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ventanilla Única · Alcaldía de Río Viejo</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono&display=swap" rel="stylesheet"/>
  <!-- Gov.co CDN -->
  <link rel="stylesheet" href="https://cdn.www.gov.co/v4/css/cdn.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Header Gov.co ── */
    .gov-header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 48px;
      background-color: #0066cc;
      display: flex;
      align-items: center;
      padding: 0 16px;
      z-index: 1000;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .gov-header a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
    }
    .gov-header .lang-selector {
      margin-left: auto;
    }
    .gov-header .lang-selector button {
      background-color: rgba(255,255,255,0.2);
      border: 1px solid rgba(255,255,255,0.3);
      color: white;
      padding: 4px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      transition: background-color 0.2s;
    }
    .gov-header .lang-selector button:hover {
      background-color: rgba(255,255,255,0.3);
    }

    body {
      font-family: 'DM Sans', system-ui, sans-serif;
      background: #eceae4;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      padding: 0;
      margin: 0;
      padding-top: 48px;
    }
    
    .main-wrapper {
      width: 100%;
      max-width: 1200px;
      padding: 2rem 1rem;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* ── Navegación por pestañas ── */
    .nav-tabs {
      width: 100%; max-width: 1000px;
      display: flex; gap: 8px; margin-bottom: 1.5rem;
      background: #fff; border-radius: 12px; padding: 8px;
      border: 0.5px solid #d8d5ce; box-shadow: 0 2px 8px rgba(26,58,92,0.06);
    }

    .nav-tab {
      flex: 1; padding: 10px 14px;
      border: none; background: transparent;
      color: #888780; font-size: 13px; font-weight: 500;
      border-radius: 8px; cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      transition: background 0.2s, color 0.2s;
    }

    .nav-tab:hover { background: #f5f3f0; }

    .nav-tab.active {
      background: #1a3a5c; color: #fff;
    }

    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .logo-area {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .escudo {
      width: 52px; height: 52px;
      background: #1a3a5c;
      border-radius: 50%;
      margin: 0 auto 10px;
      display: flex; align-items: center; justify-content: center;
    }

    .escudo svg { width: 26px; height: 26px; fill: none; stroke: #a8c8e8; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }

    .logo-area h1 { font-size: 17px; font-weight: 600; color: #1a3a5c; }
    .logo-area p  { font-size: 12px; color: #888780; margin-top: 2px; }

    .card {
      background: #fff;
      border-radius: 16px;
      border: 0.5px solid #d8d5ce;
      width: 100%; max-width: 1000px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(26,58,92,0.06);
    }
    
    @media (max-width: 768px) {
      .card {
        max-width: 540px;
      }
      .nav-tabs {
        max-width: 540px;
      }
      .main-wrapper {
        padding: 1.5rem 0.5rem;
      }
    }

    .card-header {
      background: #1a3a5c;
      padding: 1.25rem 1.75rem;
      display: flex; align-items: center; justify-content: space-between;
    }

    .card-header-text h2 { color: #fff; font-size: 15px; font-weight: 500; }
    .card-header-text span { color: #7eb5d8; font-size: 12px; }

    .radicado-badge {
      background: #2a5080; border-radius: 8px;
      padding: 6px 14px; text-align: center;
    }
    .radicado-badge .label { color: #7eb5d8; font-size: 10px; letter-spacing: 0.06em; text-transform: uppercase; }
    .radicado-badge .value { color: #fff; font-family: 'DM Mono', monospace; font-size: 15px; font-weight: 700; letter-spacing: 0.05em; }

    .section { padding: 1.25rem 1.75rem; border-bottom: 0.5px solid #ece9e2; }
    .section:last-child { border-bottom: none; }

    .section-title {
      font-size: 10px; font-weight: 500;
      letter-spacing: 0.08em; text-transform: uppercase;
      color: #888780; margin-bottom: 1rem;
    }

    .field { margin-bottom: 0.85rem; }
    .field:last-child { margin-bottom: 0; }

    label { display: block; font-size: 12px; font-weight: 500; color: #5F5E5A; margin-bottom: 5px; }

    input, select, textarea {
      width: 100%; padding: 9px 12px;
      border: 0.5px solid #D3D1C7; border-radius: 8px;
      font-size: 13px; color: #2C2C2A;
      background: #faf9f6;
      font-family: 'DM Sans', sans-serif;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
      appearance: none;
    }

    input::placeholder, textarea::placeholder { color: #B4B2A9; }

    input:focus, select:focus, textarea:focus {
      border-color: #1a3a5c;
      box-shadow: 0 0 0 3px rgba(26,58,92,0.1);
      background: #fff;
    }

    textarea { resize: vertical; min-height: 110px; line-height: 1.6; }

    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    
    @media (max-width: 640px) {
      .row { grid-template-columns: 1fr; }
    }

    select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 30px;
    }

    /* ── Urgencia ── */
    .urgencia-box {
      border-radius: 10px;
      padding: 14px 16px;
      display: flex; align-items: center; gap: 14px;
      margin-top: 10px;
      border: 0.5px solid transparent;
      transition: background 0.3s, border-color 0.3s;
    }

    .urgencia-box.verde  { background: #e6f4ea; border-color: #b6ddbf; }
    .urgencia-box.amarillo { background: #fef9e6; border-color: #f5dfa0; }
    .urgencia-box.naranja  { background: #fff3e8; border-color: #f5c890; }
    .urgencia-box.rojo     { background: #fcebeb; border-color: #f7c1c1; }
    .urgencia-box.vencido  { background: #f5e6f8; border-color: #d9b0e8; }
    .urgencia-box.oculto   { display: none; }

    .urgencia-circle {
      width: 48px; height: 48px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; font-weight: 700; font-family: 'DM Mono', monospace;
      flex-shrink: 0;
    }

    .verde   .urgencia-circle { background: #b6ddbf; color: #1a5c2a; }
    .amarillo .urgencia-circle { background: #f5dfa0; color: #7a5a00; }
    .naranja  .urgencia-circle { background: #f5c890; color: #7a3c00; }
    .rojo     .urgencia-circle { background: #f7c1c1; color: #7a1a1a; }
    .vencido  .urgencia-circle { background: #d9b0e8; color: #5a1a7a; }

    .urgencia-texto .dias-label { font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.06em; }
    .verde    .dias-label { color: #2d7a3a; }
    .amarillo .dias-label { color: #8a6a00; }
    .naranja  .dias-label { color: #8a4400; }
    .rojo     .dias-label { color: #8a2020; }
    .vencido  .dias-label { color: #6a2090; }

    .urgencia-texto .dias-desc { font-size: 12px; color: #666; margin-top: 2px; }

    /* Barra de progreso */
    .progreso-wrap { margin-top: 10px; }
    .progreso-track {
      height: 5px; background: #e8e4dc; border-radius: 10px; overflow: hidden;
    }
    .progreso-fill {
      height: 100%; border-radius: 10px;
      transition: width 0.4s ease, background 0.4s ease;
    }
    .progreso-labels {
      display: flex; justify-content: space-between;
      font-size: 11px; color: #aaa; margin-top: 4px;
    }

    /* ── Adjunto ── */
    .drop-area {
      border: 1.5px dashed #D3D1C7;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.15s, background 0.15s;
      background: #faf9f6;
      position: relative;
    }

    .drop-area:hover, .drop-area.drag { border-color: #1a3a5c; background: #f0f4f8; }

    .drop-area input[type=file] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer;
      width: 100%; height: 100%; padding: 0; border: none; background: none;
    }

    .drop-icon { margin-bottom: 8px; }
    .drop-icon svg { width: 28px; height: 28px; stroke: #888780; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
    .drop-title { font-size: 13px; font-weight: 500; color: #444; }
    .drop-sub   { font-size: 12px; color: #888780; margin-top: 3px; }

    .file-preview {
      display: none;
      align-items: center; gap: 10px;
      background: #f0f4f8;
      border: 0.5px solid #b5d4f4;
      border-radius: 8px;
      padding: 10px 14px;
      margin-top: 10px;
    }

    .file-preview.visible { display: flex; }

    .file-icon { width: 32px; height: 32px; border-radius: 6px; background: #185FA5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .file-icon svg { width: 16px; height: 16px; stroke: #fff; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .file-name { font-size: 13px; font-weight: 500; color: #1a3a5c; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; }
    .file-size { font-size: 11px; color: #888; }
    .file-remove { background: none; border: none; cursor: pointer; color: #A32D2D; font-size: 18px; line-height: 1; padding: 0 4px; }

    /* ── Botón enviar ── */
    .btn {
      width: 100%; padding: 11px;
      background: #1a3a5c; color: #fff;
      border: none; border-radius: 8px;
      font-size: 14px; font-weight: 500;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      transition: background 0.15s, transform 0.1s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      margin-top: 0.5rem;
    }
    .btn:hover { background: #254e7a; }
    .btn:active { transform: scale(0.98); }
    .btn:disabled { background: #B4B2A9; cursor: not-allowed; transform: none; }

    .status {
      font-size: 13px; text-align: center;
      padding: 10px 14px; border-radius: 8px;
      margin-top: 12px; display: none; line-height: 1.5;
    }
    .status.ok  { display: block; background: #e6f4ea; color: #2d7a3a; border: 0.5px solid #b6ddbf; }
    .status.err { display: block; background: #FCEBEB; color: #A32D2D; border: 0.5px solid #F7C1C1; }

    .footer-card {
      background: #f8f6f2; border-top: 0.5px solid #ece9e2;
      padding: 1rem 1.75rem;
      display: flex; align-items: center; justify-content: space-between;
    }
    .footer-card .left { font-size: 12px; color: #888780; line-height: 1.6; }
    .footer-card .left strong { color: #1a3a5c; font-weight: 500; display: block; font-size: 13px; }

    .footer-page { text-align: center; margin-top: 1.25rem; font-size: 11px; color: #B4B2A9; }

    /* ── Tabla de notificaciones ── */
    .notifications-table {
      width: 100%; border-collapse: collapse;
      font-size: 13px;
    }

    .notifications-table thead {
      background: #f5f3f0; border-bottom: 0.5px solid #ece9e2;
    }

    .notifications-table th {
      padding: 12px 14px; text-align: left;
      font-weight: 500; color: #666; font-size: 12px;
      letter-spacing: 0.05em; text-transform: uppercase;
    }

    .notifications-table td {
      padding: 12px 14px; border-bottom: 0.5px solid #ece9e2;
      color: #2C2C2A;
    }

    .notifications-table tbody tr:hover {
      background: #faf9f6;
    }

    .radicado-link {
      color: #1a3a5c; font-weight: 600;
      text-decoration: none; cursor: pointer;
    }

    .radicado-link:hover { text-decoration: underline; }

    .status-badge {
      display: inline-block;
      padding: 5px 10px; border-radius: 6px;
      font-size: 11px; font-weight: 500;
      letter-spacing: 0.05em; text-transform: uppercase;
    }

    .status-badge.recibido { background: #e6f4ea; color: #2d7a3a; }
    .status-badge.tramite { background: #fef9e6; color: #7a5a00; }
    .status-badge.completado { background: #e6f0ff; color: #1a3a7a; }

    .urgencia-mini {
      display: inline-block;
      width: 20px; height: 20px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700;
      text-align: center;
    }

    .urgencia-mini.verde { background: #b6ddbf; color: #1a5c2a; }
    .urgencia-mini.amarillo { background: #f5dfa0; color: #7a5a00; }
    .urgencia-mini.naranja { background: #f5c890; color: #7a3c00; }
    .urgencia-mini.rojo { background: #f7c1c1; color: #7a1a1a; }

    .empty-state {
      text-align: center; padding: 40px 20px;
      color: #888780;
    }

    .empty-state svg {
      width: 48px; height: 48px;
      stroke: #D3D1C7; fill: none; stroke-width: 1;
      margin-bottom: 12px;
    }

    .empty-state p {
      font-size: 14px; margin-top: 8px;
    }

    /* ── Modal detalle ── */
    .modal {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.4);
      z-index: 1000;
      align-items: center; justify-content: center;
    }

    .modal.active { display: flex; }

    .modal-content {
      background: #fff;
      border-radius: 16px;
      width: 90%; max-width: 500px;
      max-height: 80vh; overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }

    .modal-header {
      background: #1a3a5c;
      color: #fff;
      padding: 20px;
      display: flex; justify-content: space-between; align-items: center;
      border-radius: 16px 16px 0 0;
    }

    .modal-header h3 { font-size: 16px; font-weight: 500; }

    .modal-close {
      background: none; border: none;
      color: #fff; font-size: 24px;
      cursor: pointer;
    }

    .modal-body {
      padding: 20px;
    }

    .detail-row {
      margin-bottom: 14px;
      border-bottom: 0.5px solid #ece9e2;
      padding-bottom: 12px;
    }

    .detail-row:last-child { border-bottom: none; }

    .detail-label {
      font-size: 11px; color: #888780;
      text-transform: uppercase; letter-spacing: 0.05em;
      font-weight: 500; margin-bottom: 4px;
    }

    .detail-value {
      font-size: 13px; color: #2C2C2A;
      line-height: 1.5;
    }

    /* ── Footer Gov.co ── */
    .gov-footer {
      background-color: #0066cc;
      color: white;
      margin-top: 60px;
      width: 100%;
    }
    .gov-footer-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 40px 20px;
    }
    .gov-footer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
      margin-bottom: 30px;
    }
    .gov-footer-col h3 {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .gov-footer-col p {
      font-size: 12px;
      line-height: 1.6;
      margin-bottom: 8px;
    }
    .gov-footer-col a {
      color: #e6f0ff;
      text-decoration: none;
      font-size: 12px;
    }
    .gov-footer-col a:hover {
      text-decoration: underline;
    }
    .gov-footer-divider {
      border-top: 1px solid rgba(255,255,255,0.2);
      padding-top: 20px;
      text-align: center;
      font-size: 11px;
    }
    .gov-footer-divider a {
      margin: 0 12px;
    }
  </style>
</head>
<body>

<!-- Header Gov.co Barra Superior -->
<header class="gov-header">
  <a href="https://www.gov.co/home/">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
    </svg>
    GOV.CO
  </a>
  <div class="lang-selector">
    <button type="button" title="English">EN</button>
  </div>
</header>

<div class="main-wrapper">

<div class="logo-area">
  <div class="escudo">
    <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
  </div>
  <h1>Alcaldía Municipal de Río Viejo</h1>
  <p>Bolívar, Colombia · Sistema de Ventanilla Única</p>
</div>

<!-- Navegación de pestañas -->
<div class="nav-tabs">
  <button class="nav-tab active" onclick="mostrarTab('ventanilla')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; margin-right:4px;"><path d="M8 12h8M12 8v8M6 6h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
    Ventanilla
  </button>
  <button class="nav-tab" onclick="mostrarTab('secretaria')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; margin-right:4px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    Secretaría
  </button>
  <button class="nav-tab" onclick="mostrarTab('seguimiento')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; margin-right:4px;"><polyline points="23 6 13.5 15.5 8 10 1 17"/><polyline points="17 6 23 6 23 12"/></svg>
    Seguimiento
  </button>
</div>

<!-- ═════════════════════════════════════════ VENTANILLA ═════════════════════════════════════════ -->
<div class="tab-content active" id="tab-ventanilla">
  <div class="card">

  <!-- Header -->
  <div class="card-header">
    <div class="card-header-text">
      <h2>Registrar solicitud recibida</h2>
      <span>Ventanilla Única de Atención</span>
    </div>
    <div class="radicado-badge">
      <div class="label">Radicado</div>
      <div class="value" id="radicadoDisplay">—</div>
    </div>
  </div>

  <!-- Trámite -->
  <div class="section">
    <div class="section-title">Datos del trámite</div>

    <div class="field">
      <label for="tramite">Tipo de trámite</label>
      <select id="tramite">
        <option value="">Seleccionar trámite...</option>
        <option value="Certificado de residencia">Certificado de residencia</option>
        <option value="Permiso de construcción">Permiso de construcción</option>
        <option value="Paz y salvo municipal">Paz y salvo municipal</option>
        <option value="Solicitud de información pública">Solicitud de información pública</option>
        <option value="Derecho de petición">Derecho de petición</option>
        <option value="Registro civil">Registro civil</option>
        <option value="Certificado de estratificación">Certificado de estratificación</option>
        <option value="Solicitud de subsidio">Solicitud de subsidio</option>
        <option value="Queja o reclamo">Queja o reclamo</option>
        <option value="Otro trámite">Otro trámite</option>
      </select>
    </div>

    <div class="row">
      <div class="field">
        <label for="radicadoInput">N° de radicado</label>
        <input id="radicadoInput" type="text" placeholder="Ej: RV-2026-0412"
          oninput="document.getElementById('radicadoDisplay').textContent=this.value||'—'" />
      </div>
      <div class="field">
        <label for="fechaRadicado">Fecha de radicación</label>
        <input id="fechaRadicado" type="date" onchange="calcularUrgencia()" />
      </div>
    </div>

    <div class="row">
      <div class="field">
        <label for="plazo">Plazo legal (días hábiles)</label>
        <select id="plazo" onchange="calcularUrgencia()">
          <option value="15">15 días — Derecho de petición general</option>
          <option value="10">10 días — Info. pública (PQRS)</option>
          <option value="30">30 días — Permiso / licencia</option>
          <option value="5">5 días — Urgente / acción de tutela</option>
        </select>
      </div>
      <div class="field">
        <label for="secretaria">Secretaría responsable</label>
        <select id="secretaria">
          <option value="sg@rioviejo-bolivar.gov.co">Secretaría de Gobierno</option>
          <option value="planeacion@rioviejo-bolivar.gov.co">Planeación Municipal</option>
          <option value="hacienda@rioviejo-bolivar.gov.co">Hacienda</option>
          <option value="bienestar@rioviejo-bolivar.gov.co">Bienestar Social</option>
          <option value="recursostecnologicos@rioviejo-bolivar.gov.co">Recursos Tecnológicos</option>
        </select>
      </div>
    </div>

    <!-- Indicador de urgencia -->
    <div class="urgencia-box oculto" id="urgenciaBox">
      <div class="urgencia-circle" id="urgenciaDias">—</div>
      <div class="urgencia-texto">
        <div class="dias-label" id="urgenciaLabel">—</div>
        <div class="dias-desc" id="urgenciaDesc">—</div>
      </div>
    </div>

    <!-- Barra de progreso -->
    <div class="progreso-wrap" id="progresoWrap" style="display:none; margin-top:10px;">
      <div class="progreso-track">
        <div class="progreso-fill" id="progresoFill"></div>
      </div>
      <div class="progreso-labels">
        <span>Radicación</span>
        <span id="progresoVence">Vencimiento</span>
      </div>
    </div>
  </div>

  <!-- Ciudadano -->
  <div class="section">
    <div class="section-title">Datos del ciudadano</div>
    <div class="row">
      <div class="field">
        <label for="name">Nombre completo</label>
        <input id="name" type="text" placeholder="Nombre del peticionario" />
      </div>
      <div class="field">
        <label for="to">Correo electrónico</label>
        <input id="to" type="email" placeholder="correo@ejemplo.com" />
      </div>
    </div>
  </div>

  <!-- Documento escaneado -->
  <div class="section">
    <div class="section-title">Documento físico recibido</div>

    <div class="drop-area" id="dropArea"
      ondragover="ev(event,'drag',true)" ondragleave="ev(event,'drag',false)" ondrop="onDrop(event)">
      <input type="file" id="fileInput" accept=".pdf,.jpg,.jpeg,.png,.tiff"
        onchange="onFileChange(this.files[0])" />
      <div class="drop-icon">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
      </div>
      <div class="drop-title">Adjuntar documento escaneado</div>
      <div class="drop-sub">PDF, JPG o PNG · máx. 50 MB · Arrastra o haz clic</div>
    </div>

    <div class="file-preview" id="filePreview">
      <div class="file-icon">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div style="flex:1; overflow:hidden;">
        <div class="file-name" id="fileName">—</div>
        <div class="file-size" id="fileSize">—</div>
      </div>
      <button class="file-remove" onclick="quitarArchivo()" title="Quitar archivo">×</button>
    </div>
  </div>

  <!-- Mensaje a secretaría -->
  <div class="section">
    <div class="section-title">Mensaje a la secretaría</div>
    <div class="field">
      <label for="msg">Observaciones o instrucciones</label>
      <textarea id="msg" placeholder="Descripción de la solicitud, observaciones relevantes, instrucciones para la secretaría..."></textarea>
    </div>

    <button class="btn" id="sendBtn" onclick="enviar()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      Enviar a secretaría responsable
    </button>
    <div class="status" id="status"></div>
  </div>

  <div class="footer-card">
    <div class="left">
      <strong>Alcaldía Municipal de Río Viejo</strong>
      Bolívar, Colombia · NIT 802.003.882-8<br/>
      Carrera 5 # 3-25 · Tel: (605) 123-4567
    </div>
    <div style="font-size:11px;color:#B4B2A9;text-align:right;">
      Powered by EmailJS<br/>Sin servidor requerido
    </div>
  </div>
  </div>
</div>

<!-- ═════════════════════════════════════════ SECRETARÍA ═════════════════════════════════════════ -->
<div class="tab-content" id="tab-secretaria">
  <div class="card">

  <!-- Header -->
  <div class="card-header">
    <div class="card-header-text">
      <h2>Notificaciones recibidas</h2>
      <span>Panel de recepción para secretarías</span>
    </div>
    <div class="radicado-badge">
      <div class="label">Total</div>
      <div class="value" id="totalNotificaciones">0</div>
    </div>
  </div>

  <div class="section">
    <table class="notifications-table" id="notificationsTable">
      <thead>
        <tr>
          <th>Radicado</th>
          <th>Trámite</th>
          <th>Ciudadano</th>
          <th>Urgencia</th>
          <th>Estado</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody id="notificationsBody">
        <tr>
          <td colspan="6" class="empty-state" style="padding: 40px 20px;">
            <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <p>Sin notificaciones recibidas aún</p>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="footer-card">
    <div class="left">
      <strong>Instrucciones:</strong> Haz clic en cualquier radicado para ver detalles completos de la solicitud.
    </div>
  </div>
  </div>
</div>

<!-- ═════════════════════════════════════════ SEGUIMIENTO ═════════════════════════════════════════ -->
<div class="tab-content" id="tab-seguimiento">
  <div class="card">

  <!-- Header -->
  <div class="card-header">
    <div class="card-header-text">
      <h2>Seguimiento de solicitudes</h2>
      <span>Estado y progreso de tramitación</span>
    </div>
    <div class="radicado-badge">
      <div class="label">Activas</div>
      <div class="value" id="totalActivas">0</div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Solicitudes en proceso</div>
    <table class="notifications-table" id="seguimientoTable">
      <thead>
        <tr>
          <th>Radicado</th>
          <th>Trámite</th>
          <th>Secretaría</th>
          <th>Urgencia</th>
          <th>Progreso</th>
        </tr>
      </thead>
      <tbody id="seguimientoBody">
        <tr>
          <td colspan="5" class="empty-state" style="padding: 40px 20px;">
            <svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8 10 1 17"/><polyline points="17 6 23 6 23 12"/></svg>
            <p>Sin solicitudes en seguimiento</p>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="footer-card">
    <div class="left">
      <strong>Leyenda de urgencia:</strong> 🟢 Verde (6+ días) | 🟡 Amarillo (3-5 días) | 🟠 Naranja (urgente) | 🔴 Rojo (crítico)
    </div>
  </div>
  </div>
</div>

<!-- Modal de detalles -->
<div class="modal" id="detailModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Detalles de la solicitud</h3>
      <button class="modal-close" onclick="cerrarModal()">×</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>

<div class="footer-page">
  Sistema de Ventanilla Única · Alcaldía de Río Viejo · Bolívar · Correo oficial
</div>

<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script>
  var SERVICE_ID  = '';
  var TEMPLATE_ID = '';
  var PUBLIC_KEY  = '';

  var archivoBase64 = null;
  var archivoNombre = '';
  var archivoTipo   = '';

  // Almacenamiento de notificaciones
  var notificaciones = [];

  // Fecha hoy por defecto
  document.getElementById('fechaRadicado').value = new Date().toISOString().split('T')[0];
  calcularUrgencia();

  // ── Navegación de pestañas ──
  function mostrarTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
  }

  // ── Días hábiles ──
  function esHabil(fecha) {
    var d = fecha.getDay();
    return d !== 0 && d !== 6;
  }

  function diasHabilesEntre(desde, hasta) {
    var count = 0;
    var cur = new Date(desde);
    cur.setDate(cur.getDate() + 1);
    while (cur <= hasta) {
      if (esHabil(cur)) count++;
      cur.setDate(cur.getDate() + 1);
    }
    return count;
  }

  function sumarDiasHabiles(desde, n) {
    var cur = new Date(desde);
    var cont = 0;
    while (cont < n) {
      cur.setDate(cur.getDate() + 1);
      if (esHabil(cur)) cont++;
    }
    return cur;
  }

  function calcularUrgencia() {
    var fechaVal = document.getElementById('fechaRadicado').value;
    var plazo    = parseInt(document.getElementById('plazo').value);
    var box      = document.getElementById('urgenciaBox');
    var wrap     = document.getElementById('progresoWrap');

    if (!fechaVal) { box.className = 'urgencia-box oculto'; wrap.style.display='none'; return; }

    var desde    = new Date(fechaVal + 'T00:00:00');
    var vence    = sumarDiasHabiles(desde, plazo);
    var hoy      = new Date(); hoy.setHours(0,0,0,0);
    var restantes = diasHabilesEntre(hoy, vence);
    var transcurridos = diasHabilesEntre(desde, hoy < desde ? desde : hoy);
    var porcentaje = Math.min(100, Math.round((transcurridos / plazo) * 100));

    var nivel, colorFill, labelTxt, descTxt;

    if (restantes < 0) {
      nivel = 'vencido'; colorFill = '#c084e8';
      labelTxt = '¡Plazo vencido!';
      descTxt  = 'Han pasado ' + Math.abs(restantes) + ' días hábiles del vencimiento.';
    } else if (restantes === 0) {
      nivel = 'rojo'; colorFill = '#e24b4a';
      labelTxt = 'Vence hoy';
      descTxt  = 'El plazo legal termina hoy. Respuesta inmediata requerida.';
    } else if (restantes <= 2) {
      nivel = 'rojo'; colorFill = '#e24b4a';
      labelTxt = restantes + ' día' + (restantes>1?'s':'') + ' hábil' + (restantes>1?'es':'');
      descTxt  = 'Urgente — respuesta requerida en menos de 3 días hábiles.';
    } else if (restantes <= 5) {
      nivel = 'naranja'; colorFill = '#ef9f27';
      labelTxt = restantes + ' días hábiles';
      descTxt  = 'Prioridad alta — gestionar esta semana.';
    } else if (restantes <= 10) {
      nivel = 'amarillo'; colorFill = '#ba7517';
      labelTxt = restantes + ' días hábiles';
      descTxt  = 'En seguimiento — revisar pronto.';
    } else {
      nivel = 'verde'; colorFill = '#639922';
      labelTxt = restantes + ' días hábiles';
      descTxt  = 'Dentro del plazo — vence el ' + vence.toLocaleDateString('es-CO', {day:'2-digit',month:'long',year:'numeric'});
    }

    box.className = 'urgencia-box ' + nivel;
    document.getElementById('urgenciaDias').textContent = restantes < 0 ? '!' : restantes;
    document.getElementById('urgenciaLabel').textContent = labelTxt;
    document.getElementById('urgenciaDesc').textContent  = descTxt;

    wrap.style.display = 'block';
    document.getElementById('progresoFill').style.width      = porcentaje + '%';
    document.getElementById('progresoFill').style.background = colorFill;
    document.getElementById('progresoVence').textContent =
      vence.toLocaleDateString('es-CO', {day:'2-digit', month:'short'});
  }

  // ── Archivo ──
  function ev(e, cls, add) {
    e.preventDefault();
    document.getElementById('dropArea').classList[add?'add':'remove'](cls);
  }

  function onDrop(e) {
    e.preventDefault();
    document.getElementById('dropArea').classList.remove('drag');
    var f = e.dataTransfer.files[0];
    if (f) onFileChange(f);
  }

  function onFileChange(f) {
    if (!f) return;
    if (f.size > 5000 * 1024 * 1024) { alert('El archivo supera 50 MB.'); return; }
    archivoNombre = f.name;
    archivoTipo   = f.type;
    var reader = new FileReader();
    reader.onload = function(e) {
      archivoBase64 = e.target.result.split(',')[1];
      document.getElementById('fileName').textContent = f.name;
      document.getElementById('fileSize').textContent = (f.size/1024/1024).toFixed(2) + ' MB';
      document.getElementById('filePreview').classList.add('visible');
      document.getElementById('dropArea').style.display = 'none';
    };
    reader.readAsDataURL(f);
  }

  function quitarArchivo() {
    archivoBase64 = null; archivoNombre = ''; archivoTipo = '';
    document.getElementById('filePreview').classList.remove('visible');
    document.getElementById('dropArea').style.display = 'block';
    document.getElementById('fileInput').value = '';
  }

  // ── Utilidades ──
  function generarRadicado() {
    var anio = new Date().getFullYear();
    var num  = String(Math.floor(Math.random()*9000)+1000);
    return 'RV-' + anio + '-' + num;
  }

  function formatFecha(val) {
    if (!val) return '';
    var p = val.split('-');
    return p[2]+'/'+p[1]+'/'+p[0];
  }

  function mostrarError(msg) {
    var st = document.getElementById('status');
    st.className = 'status err'; st.textContent = msg;
  }

  function getUrgencyBadge(dias) {
    var clase = 'verde';
    if (dias < 0) clase = 'rojo';
    else if (dias <= 2) clase = 'rojo';
    else if (dias <= 5) clase = 'naranja';
    else if (dias <= 10) clase = 'amarillo';
    return '<span class="urgencia-mini ' + clase + '">' + (dias < 0 ? '!' : dias) + '</span>';
  }

  function getUrgencyLabel(dias) {
    if (dias < 0) return 'Vencido';
    if (dias === 0) return 'Vence hoy';
    if (dias <= 2) return dias + 'd urgente';
    if (dias <= 5) return dias + 'd prioridad';
    if (dias <= 10) return dias + 'd seguimiento';
    return dias + 'd disponible';
  }

  // ── Panel Secretaría ──
  function actualizarNotificaciones() {
    var tbody = document.getElementById('notificationsBody');
    document.getElementById('totalNotificaciones').textContent = notificaciones.length;

    if (notificaciones.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="empty-state" style="padding: 40px 20px;"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><p>Sin notificaciones recibidas aún</p></td></tr>';
      return;
    }

    var html = '';
    notificaciones.forEach(function(notif, idx) {
      html += '<tr onclick="mostrarDetalles(' + idx + ')" style="cursor:pointer;">' +
        '<td><strong class="radicado-link">' + notif.radicado + '</strong></td>' +
        '<td>' + notif.tramite + '</td>' +
        '<td>' + notif.name + '</td>' +
        '<td style="text-align:center;">' + getUrgencyBadge(notif.dias) + '</td>' +
        '<td><span class="status-badge tramite">En trámite</span></td>' +
        '<td>' + new Date(notif.fecha).toLocaleDateString('es-CO') + '</td>' +
        '</tr>';
    });

    tbody.innerHTML = html;
  }

  function actualizarSeguimiento() {
    var tbody = document.getElementById('seguimientoBody');
    document.getElementById('totalActivas').textContent = notificaciones.length;

    if (notificaciones.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="empty-state" style="padding: 40px 20px;"><svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8 10 1 17"/><polyline points="17 6 23 6 23 12"/></svg><p>Sin solicitudes en seguimiento</p></td></tr>';
      return;
    }

    var html = '';
    notificaciones.forEach(function(notif, idx) {
      var progreso = Math.min(100, Math.round((10 - Math.max(0, notif.dias)) / 10 * 100));
      html += '<tr onclick="mostrarDetalles(' + idx + ')" style="cursor:pointer;">' +
        '<td><strong class="radicado-link">' + notif.radicado + '</strong></td>' +
        '<td>' + notif.tramite + '</td>' +
        '<td>' + notif.secretaria_nombre + '</td>' +
        '<td style="text-align:center;">' + getUrgencyBadge(notif.dias) + '</td>' +
        '<td><div style="background:#e8e4dc; border-radius:4px; height:6px; width:100%;"><div style="background:' + (notif.dias >= 5 ? '#639922' : notif.dias >= 2 ? '#ba7517' : '#e24b4a') + '; height:100%; border-radius:4px; width:' + progreso + '%;"></div></div></td>' +
        '</tr>';
    });

    tbody.innerHTML = html;
  }

  function mostrarDetalles(idx) {
    var notif = notificaciones[idx];
    var modal = document.getElementById('detailModal');
    var body = document.getElementById('modalBody');

    var secretaria_nombre = {
      'sg@rioviejo-bolivar.gov.co': 'Secretaría de Gobierno',
      'planeacion@rioviejo-bolivar.gov.co': 'Planeación Municipal',
      'hacienda@rioviejo-bolivar.gov.co': 'Hacienda',
      'bienestar@rioviejo-bolivar.gov.co': 'Bienestar Social',
      'recursostecnologicos@rioviejo-bolivar.gov.co': 'Recursos Tecnológicos'
    }[notif.secretaria] || notif.secretaria;

    body.innerHTML = '<div class="detail-row">' +
      '<div class="detail-label">Radicado</div>' +
      '<div class="detail-value" style="font-family:\'DM Mono\'; font-weight:600; font-size:14px;">' + notif.radicado + '</div>' +
      '</div>' +
      '<div class="detail-row">' +
      '<div class="detail-label">Trámite</div>' +
      '<div class="detail-value">' + notif.tramite + '</div>' +
      '</div>' +
      '<div class="detail-row">' +
      '<div class="detail-label">Ciudadano</div>' +
      '<div class="detail-value">' + notif.name + ' (' + notif.email + ')</div>' +
      '</div>' +
      '<div class="detail-row">' +
      '<div class="detail-label">Secretaría responsable</div>' +
      '<div class="detail-value">' + secretaria_nombre + '</div>' +
      '</div>' +
      '<div class="detail-row">' +
      '<div class="detail-label">Fecha de radicación</div>' +
      '<div class="detail-value">' + notif.fecha_format + '</div>' +
      '</div>' +
      '<div class="detail-row">' +
      '<div class="detail-label">Plazo legal</div>' +
      '<div class="detail-value">' + notif.plazo_texto + ' (Vence: ' + notif.vence_format + ')</div>' +
      '</div>' +
      '<div class="detail-row">' +
      '<div class="detail-label">Urgencia</div>' +
      '<div class="detail-value">' + getUrgencyLabel(notif.dias) + ' - ' + notif.urgencia_label + '</div>' +
      '</div>' +
      '<div class="detail-row">' +
      '<div class="detail-label">Observaciones</div>' +
      '<div class="detail-value">' + (notif.mensaje || '(Sin observaciones)') + '</div>' +
      '</div>';

    modal.classList.add('active');
  }

  function cerrarModal() {
    document.getElementById('detailModal').classList.remove('active');
  }

  // ── Enviar ──
  function enviar() {
    var tramite   = document.getElementById('tramite').value;
    var radicado  = document.getElementById('radicadoInput').value.trim();
    var fechaVal  = document.getElementById('fechaRadicado').value;
    var plazo     = document.getElementById('plazo').value;
    var secretaria = document.getElementById('secretaria').value;
    var name      = document.getElementById('name').value.trim();
    var to        = document.getElementById('to').value.trim();
    var msg       = document.getElementById('msg').value.trim();
    var st        = document.getElementById('status');
    var btn       = document.getElementById('sendBtn');

    st.className = 'status';

    if (!tramite)    { mostrarError('Selecciona el tipo de trámite.'); return; }
    if (!name)       { mostrarError('Ingresa el nombre del ciudadano.'); return; }
    if (!to)         { mostrarError('Ingresa el correo del ciudadano.'); return; }

    if (!radicado) {
      radicado = generarRadicado();
      document.getElementById('radicadoInput').value = radicado;
      document.getElementById('radicadoDisplay').textContent = radicado;
    }

    var now      = new Date();
    var hora     = now.toLocaleString('es-CO', {hour:'2-digit', minute:'2-digit', hour12:true});
    var fechaStr = formatFecha(fechaVal) || formatFecha(now.toISOString().split('T')[0]);

    var desde    = new Date(fechaVal + 'T00:00:00');
    var plazoInt = parseInt(plazo);
    var vence    = sumarDiasHabiles(desde, plazoInt);
    var hoy      = new Date(); hoy.setHours(0,0,0,0);
    var restantes = diasHabilesEntre(hoy, vence);

    var plazoTexto = plazo + ' días hábiles';
    var urgTxt    = document.getElementById('urgenciaLabel').textContent;

    // Guardar en array local
    var secretaria_nombre = {
      'sg@rioviejo-bolivar.gov.co': 'Secretaría de Gobierno',
      'planeacion@rioviejo-bolivar.gov.co': 'Planeación Municipal',
      'hacienda@rioviejo-bolivar.gov.co': 'Hacienda',
      'bienestar@rioviejo-bolivar.gov.co': 'Bienestar Social',
      'recursostecnologicos@rioviejo-bolivar.gov.co': 'Recursos Tecnológicos'
    }[secretaria] || 'Desconocida';

    var notificacion = {
      radicado: radicado,
      tramite: tramite,
      name: name,
      email: to,
      secretaria: secretaria,
      secretaria_nombre: secretaria_nombre,
      fecha: now.toISOString(),
      fecha_format: fechaStr,
      plazo_texto: plazoTexto,
      vence_format: vence.toLocaleDateString('es-CO', {day:'2-digit',month:'long',year:'numeric'}),
      dias: restantes,
      urgencia_label: urgTxt,
      mensaje: msg
    };

    notificaciones.push(notificacion);

    // Actualizar paneles
    actualizarNotificaciones();
    actualizarSeguimiento();

    emailjs.init({ publicKey: PUBLIC_KEY });
    btn.disabled = true;
    btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Enviando...';

    var params = {
      to_email:        secretaria,
      citizen_email:   to,
      name:            name,
      radicado:        radicado,
      tramite:         tramite,
      fecha_radicado:  fechaStr,
      plazo:           plazoTexto,
      urgencia:        urgTxt,
      estado:          'Recibido · En trámite',
      message:         msg || '(Sin observaciones adicionales)',
      time:            fechaStr + ' · ' + hora,
      from_name:       'Ventanilla Única · Alcaldía de Río Viejo',
      from_email:      'recursostecnologicos@rioviejo-bolivar.gov.co'
    };

    if (archivoBase64) {
      params.attachment    = archivoBase64;
      params.filename      = archivoNombre;
      params.content_type  = archivoTipo;
    }

    emailjs.send(SERVICE_ID, TEMPLATE_ID, params)
    .then(function() {
      st.className = 'status ok';
      st.innerHTML = 'Solicitud <strong>' + radicado + '</strong> enviada correctamente a la secretaría responsable.' +
        (archivoBase64 ? ' Documento adjunto incluido.' : '');
      document.getElementById('name').value = '';
      document.getElementById('to').value   = '';
      document.getElementById('msg').value  = '';
      document.getElementById('radicadoInput').value = '';
      document.getElementById('radicadoDisplay').textContent = '—';
      document.getElementById('tramite').selectedIndex = 0;
      quitarArchivo();
    }).catch(function(e) {
      st.className = 'status err';
      st.textContent = 'Error al enviar: ' + (e.text || JSON.stringify(e));
    }).finally(function() {
      btn.disabled = false;
      btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Enviar a secretaría responsable';
    });
  }

  // Cerrar modal al hacer clic afuera
  document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
  });
</script>

<!-- Footer Gov.co Barra Inferior -->
<footer class="gov-footer">
  <div class="gov-footer-content">
    <div class="gov-footer-grid">
      <div class="gov-footer-col">
        <h3>Alcaldía de Río Viejo</h3>
        <p><strong>Dirección:</strong><br>Calle Principal No. 1-50<br>Río Viejo, Bolívar, Colombia</p>
        <p><strong>Código Postal:</strong> 130001</p>
      </div>
      <div class="gov-footer-col">
        <h3>Horario de Atención</h3>
        <p>Lunes a Viernes<br>8:00 am - 5:00 pm</p>
        <p><strong>Ventanilla Única:</strong><br>Todos los días hábiles</p>
      </div>
      <div class="gov-footer-col">
        <h3>Contacto</h3>
        <p><strong>Teléfono:</strong><br>+57 (5) 123-4567</p>
        <p><strong>Línea gratuita:</strong><br>01-8000-123456</p>
        <p><strong>Correo:</strong><br><a href="mailto:info@rioviejo-bolivar.gov.co">info@rioviejo-bolivar.gov.co</a></p>
      </div>
      <div class="gov-footer-col">
        <h3>Redes Sociales</h3>
        <p>
          <a href="#">Facebook</a><br>
          <a href="#">Twitter</a><br>
          <a href="#">Instagram</a>
        </p>
      </div>
    </div>
    <div class="gov-footer-divider">
      <p>© 2026 Alcaldía Municipal de Río Viejo. Todos los derechos reservados.
      <a href="#">Términos y Condiciones</a> |
      <a href="#">Aviso de Privacidad</a> |
      <a href="#">Mapa del Sitio</a></p>
    </div>
  </div>
</footer>

</body>
</html>
