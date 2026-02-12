<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Guia de Ajuda</h1>
                    <p class="text-muted mb-0">Documentação do sistema para novos usuários e pesquisas</p>
                </div>
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
            
            <?php if ($conteudo): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="markdown-content">
                            <?php
                            // Converter Markdown para HTML básico
                            $html = $conteudo;
                            
                            // Títulos
                            $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
                            $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
                            $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
                            
                            // Negrito
                            $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
                            
                            // Itálico
                            $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
                            
                            // Listas
                            $html = preg_replace('/^- (.*$)/m', '<li>$1</li>', $html);
                            $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
                            
                            // Parágrafos
                            $html = preg_replace('/\n\n/', '</p><p>', $html);
                            $html = '<p>' . $html . '</p>';
                            
                            // Links
                            $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
                            
                            echo $html;
                            ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    O arquivo de ajuda não foi encontrado. Verifique se o arquivo <code>GUIA_AJUDA.md</code> existe na raiz do projeto.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.markdown-content {
    line-height: 1.6;
}

.markdown-content h1 {
    color: #2c3e50;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
    margin-top: 30px;
    margin-bottom: 20px;
}

.markdown-content h2 {
    color: #34495e;
    border-bottom: 1px solid #ecf0f1;
    padding-bottom: 5px;
    margin-top: 25px;
    margin-bottom: 15px;
}

.markdown-content h3 {
    color: #34495e;
    margin-top: 20px;
    margin-bottom: 10px;
}

.markdown-content ul {
    margin-left: 20px;
    margin-bottom: 15px;
}

.markdown-content li {
    margin-bottom: 5px;
}

.markdown-content code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

.markdown-content p {
    margin-bottom: 15px;
}

.markdown-content strong {
    color: #2c3e50;
}

@media print {
    .btn {
        display: none;
    }
    
    .markdown-content {
        font-size: 12pt;
    }
}
</style>
