<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos a Adjuntar</title>
    <link rel="stylesheet" href="../css/agregardocumentocss.css">
</head>
<body>
    <div class="modal-documentos">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Documentos a Adjuntar</h2>
                <button class="close-button" onclick="cerrarModal()">×</button>
            </div>
            
            <div class="modal-body">
                <div class="documentos-requeridos">
                    <ul>
                        <li>-Acta de Nacimiento del Estudiante</li>
                        <li>-Cédulas de Padres o Tutores</li>
                        <li>-Record de Notas</li>
                        <li>-Foto 2x2 del Estudiante</li>
                        <li>-Carta de Buena Conducta del Centro Anterior</li>
                        <li>-Certificado Médico</li>
                        <li>-Tipificación de Sangre</li>
                    </ul>
                </div>

                <div class="area-carga">
                    <div class="dropzone" id="dropzone">
                        <p>Inserte o Arrastre los Archivos</p>
                    </div>
                </div>

                <div class="nota">
                    <p>Nota: Estos documentos deben ser entregados en físico en la fecha que se le indique posteriormente.</p>
                </div>

                <div class="buttons">
                    <button type="button" class="btn-adjuntar">
                        <span class="icon">📎</span>
                        Adjuntar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funcionalidad para el botón de cerrar
        function cerrarModal() {
            window.parent.postMessage('cerrarModal', '*');
        }

        // Funcionalidad para arrastrar y soltar archivos
        const dropzone = document.getElementById('dropzone');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropzone.classList.add('drag-hover');
        }

        function unhighlight(e) {
            dropzone.classList.remove('drag-hover');
        }

        dropzone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            // Aquí puedes agregar la lógica para manejar los archivos
            console.log(files);
        }
    </script>
</body>
</html>
