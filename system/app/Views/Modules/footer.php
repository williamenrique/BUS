    
    </div>
    
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const dateElement = document.getElementById('currentDate');
		if (dateElement) {
			const today = new Date();
			const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
			// Capitalizar la primera letra
			let dateString = today.toLocaleDateString('es-VE', options);
			dateString = dateString.charAt(0).toUpperCase() + dateString.slice(1);
			dateElement.innerHTML = `<i class="far fa-calendar-alt mr-2"></i> ${dateString}`;
		}
	});

	</script>
    <!-- jQuery -->
    <script src="<?= PLUGINS ?>jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= PLUGINS ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="<?= PLUGINS ?>overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= JS ?>adminlte.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.5/qz-tray.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
	<!-- Select2 JS -->
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<!-- (Opcional) Archivo de idioma para Select2 en español -->
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>

	<script>
		// -------------------------------------------------------------------
		// VARIABLES GLOBALES DE SESIÓN PARA JAVASCRIPT
		// Se definen en el footer para estar disponibles en todas las páginas.
		// -------------------------------------------------------------------
		const userDepartment = "<?= strtoupper(htmlspecialchars($_SESSION['userData']['departamento_nombre'] ?? 'DEFAULT', ENT_QUOTES, 'UTF-8')) ?>";
    	const userRole = "<?= strtoupper(htmlspecialchars($_SESSION['userData']['rol_nombre'] ?? 'DEFAULT', ENT_QUOTES, 'UTF-8')) ?>";
    	const userId = <?= intval($_SESSION['userData']['usuario_id'] ?? 0) ?>; // ID del usuario logueado
    	const userEstacionId = <?= intval($_SESSION['userData']['usuario_estacion_id'] ?? 0) ?>; // ID de la estación asignada al usuario
	</script>

	<script src="<?= JS ?>function.main.js?v=<?= time() ?>"></script>
	<script src="<?= JS.$data['page_functions']?>?v=<?= time() ?>"></script>
	<?php 
	// Cargar scripts adicionales si existen
	if (isset($data['page_extra_scripts']) && is_array($data['page_extra_scripts'])) {
		foreach ($data['page_extra_scripts'] as $extraScript) {
			echo '<script src="' . JS . $extraScript . '?v=' . time() . '"></script>' . "\n";
		}
	}
	?>
	<script src="<?= JS ?>function.imprimirQZ.js?v=<?= time() ?>"></script>
	<script src="<?= JS ?>notifications.js?v=<?= time() ?>"></script>
</body>
</html>
