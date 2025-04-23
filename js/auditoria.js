document.addEventListener('DOMContentLoaded', function() {
   
    const btnsWeb = document.querySelectorAll('.inicializador-actions .btn-web');
    
    btnsWeb.forEach(btn => {
        btn.addEventListener('click', function(e) {
            
            const url = this.getAttribute('href');
            const nombre = this.closest('.inicializador-card').querySelector('h3').textContent;
            
            registrarAuditoriaInicializador('Intento de ejecución Web', 
                `Inicializador: ${nombre}`);
        });
    });
    
   
    const btnsAPI = document.querySelectorAll('.inicializador-actions .btn-api');
    
    btnsAPI.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm('¿Está seguro que desea ejecutar este inicializador? Esta acción puede afectar al sistema.')) {
                return;
            }
            
            const url = this.getAttribute('href');
            const nombre = this.closest('.inicializador-card').querySelector('h3').textContent;
            const card = this.closest('.inicializador-card');
            
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'loading-indicator';
            loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ejecutando inicializador...';
            card.appendChild(loadingIndicator);
            
           
            const formData = new FormData();
            formData.append('inicializar', 'true');
            
     
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
               
                card.removeChild(loadingIndicator);
                
               
                const resultadoDiv = document.createElement('div');
                resultadoDiv.className = data.exito ? 'alert alert-success' : 'alert alert-danger';
                resultadoDiv.textContent = data.mensaje;
                card.appendChild(resultadoDiv);
                
         
                registrarAuditoriaInicializador(data.exito ? 'Ejecución API exitosa' : 'Error en ejecución API', 
                    `Inicializador: ${nombre}, Resultado: ${data.mensaje}`);
                
              
                setTimeout(() => {
                    card.removeChild(resultadoDiv);
                }, 5000);
            })
            .catch(error => {
             
                card.removeChild(loadingIndicator);
                
            
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                errorDiv.textContent = 'Error al ejecutar el inicializador: ' + error.message;
                card.appendChild(errorDiv);
                
     
                registrarAuditoriaInicializador('Error en ejecución API', 
                    `Inicializador: ${nombre}, Error: ${error.message}`);
                

                setTimeout(() => {
                    card.removeChild(errorDiv);
                }, 5000);
            });
        });
    });
    

    function registrarAuditoriaInicializador(tipoAccion, descripcion) {
        const formData = new FormData();
        formData.append('agregar_log', true);
        formData.append('tipo_accion', 'Ejecución Inicializador');
        formData.append('descripcion', descripcion);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        }).catch(error => {
            console.error('Error al registrar auditoría:', error);
        });
    }
    

    const filtroInputs = document.querySelectorAll('.filtro-container input');
    
    filtroInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
    });
}); 