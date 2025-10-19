<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oficiais de Serviço - PAPEM</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #2196F3;
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .duty-officers-container {
            background-color: #f0f4f8;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .officers-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .duty-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e6f7ff;
            border-radius: 4px;
            border-left: 4px solid #1890ff;
        }

        .current-officers {
            margin-top: 20px;
            padding: 15px;
            background-color: #f6ffed;
            border-radius: 4px;
            border-left: 4px solid #52c41a;
        }

        .error-message {
            color: #ff4d4f;
            margin-top: 5px;
            font-size: 14px;
        }

        .buttons-container {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #1890ff;
            color: white;
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
        }

        .btn-primary:hover {
            background-color: #096dd9;
        }

        .btn-secondary:hover {
            background-color: #d9d9d9;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border: 3px solid rgba(0,0,0,0.2);
            border-radius: 50%;
            border-top-color: #1890ff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <header>
        <h1>Gestão de Oficiais de Serviço</h1>
        <div class="user-info">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Bem-vindo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuário'); ?></span>
                <a href="controllers/UserController.php?method=logout">Sair</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        <div class="duty-officers-container">
            <h2>Definir Oficiais de Serviço</h2>
            
            <div id="currentOfficers" class="current-officers">
                <h3>Oficiais de Serviço Atuais</h3>
                <div id="loadingCurrentOfficers">
                    <span class="loading"></span> Carregando...
                </div>
                <div id="officersDisplay" class="hidden">
                    <p><strong>Oficial de Serviço:</strong> <span id="currentOfficer">Não definido</span></p>
                    <p><strong>Contramestre:</strong> <span id="currentMaster">Não definido</span></p>
                    <p><small>Última atualização: <span id="lastUpdated">-</span></small></p>
                </div>
                <div id="errorLoadingOfficers" class="error-message hidden">
                    Erro ao carregar os oficiais de serviço atuais.
                </div>
            </div>

            <form id="dutyOfficersForm" class="officers-form">
                <div class="form-group">
                    <label for="officerSelect">Oficial de Serviço:</label>
                    <select id="officerSelect" name="officerName">
                        <option value="">Selecione um Oficial</option>
                        <?php foreach ($oficiais as $oficial): ?>
                            <?php if (strpos(strtoupper($oficial['posto_id']), 'T') !== false): ?>
                                <option value="<?php echo htmlspecialchars($oficial['nome']); ?>">
                                    <?php echo htmlspecialchars($oficial['descricao'] . ' ' . $oficial['nome']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="masterSelect">Contramestre:</label>
                    <select id="masterSelect" name="masterName">
                        <option value="">Selecione um Contramestre</option>
                        <?php foreach ($oficiais as $oficial): ?>
                            <?php if (strpos(strtoupper($oficial['posto_id']), 'SG') !== false): ?>
                                <option value="<?php echo htmlspecialchars($oficial['nome']); ?>">
                                    <?php echo htmlspecialchars($oficial['descricao'] . ' ' . $oficial['nome']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <div class="duty-info">
                <p>Os oficiais de serviço definidos serão exibidos na barra superior do sistema PAPEM.</p>
                <p>A atualização é imediata e ficará visível para todos os usuários.</p>
            </div>

            <div id="formError" class="error-message hidden"></div>

            <div class="buttons-container">
                <button id="updateButton" class="btn btn-primary">
                    <span id="updateSpinner" class="loading hidden"></span>
                    Atualizar Oficiais de Serviço
                </button>
                <button id="cancelButton" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        // JavaScript para comunicação com a API do Node.js
        document.addEventListener('DOMContentLoaded', function() {
            // Carregar oficiais de serviço atuais ao iniciar a página
            loadCurrentDutyOfficers();

            // Evento para o botão de atualização
            document.getElementById('updateButton').addEventListener('click', function() {
                updateDutyOfficers();
            });

            // Evento para o botão de cancelar
            document.getElementById('cancelButton').addEventListener('click', function() {
                window.location.href = 'index.php';
            });
        });

        // Função para carregar os oficiais de serviço atuais
        function loadCurrentDutyOfficers() {
            document.getElementById('loadingCurrentOfficers').classList.remove('hidden');
            document.getElementById('officersDisplay').classList.add('hidden');
            document.getElementById('errorLoadingOfficers').classList.add('hidden');
            
            // Obter o cookie de sessão para autenticação
            const cookies = document.cookie.split(';').reduce((acc, cookie) => {
                const [key, value] = cookie.trim().split('=');
                acc[key] = value;
                return acc;
            }, {});
            
            // URL da API do Node.js
            const apiUrl = '/api/duty-officers';
            
            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Cookie': document.cookie // Enviar o cookie da sessão PHP
                },
                credentials: 'include' // Importante para enviar cookies
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao carregar oficiais de serviço');
                }
                return response.json();
            })
            .then(data => {
                document.getElementById('loadingCurrentOfficers').classList.add('hidden');
                
                if (data.success) {
                    document.getElementById('officersDisplay').classList.remove('hidden');
                    
                    // Atualizar os dados na interface
                    document.getElementById('currentOfficer').textContent = data.officers.officerName || 'Não definido';
                    document.getElementById('currentMaster').textContent = data.officers.masterName || 'Não definido';
                    
                    // Formatar data de atualização
                    const updatedDate = data.officers.updatedAt ? new Date(data.officers.updatedAt) : null;
                    document.getElementById('lastUpdated').textContent = updatedDate ? 
                        updatedDate.toLocaleString('pt-BR') : '-';
                    
                    // Pré-selecionar os valores nos dropdowns, se disponíveis
                    if (data.officers.officerName) {
                        selectOptionByText('officerSelect', data.officers.officerName);
                    }
                    
                    if (data.officers.masterName) {
                        selectOptionByText('masterSelect', data.officers.masterName);
                    }
                } else {
                    document.getElementById('errorLoadingOfficers').classList.remove('hidden');
                    console.error('Erro ao carregar dados:', data.error);
                }
            })
            .catch(error => {
                document.getElementById('loadingCurrentOfficers').classList.add('hidden');
                document.getElementById('errorLoadingOfficers').classList.remove('hidden');
                console.error('Erro na requisição:', error);
            });
        }

        // Função para selecionar uma opção no dropdown pelo texto
        function selectOptionByText(selectId, text) {
            const selectElement = document.getElementById(selectId);
            for (let i = 0; i < selectElement.options.length; i++) {
                const option = selectElement.options[i];
                if (option.text.includes(text) || option.value.includes(text)) {
                    selectElement.selectedIndex = i;
                    break;
                }
            }
        }

        // Função para atualizar os oficiais de serviço
        function updateDutyOfficers() {
            // Limpar mensagens de erro anteriores
            document.getElementById('formError').classList.add('hidden');
            
            // Mostrar spinner de carregamento
            const updateButton = document.getElementById('updateButton');
            const updateSpinner = document.getElementById('updateSpinner');
            updateButton.disabled = true;
            updateSpinner.classList.remove('hidden');
            
            // Obter valores dos campos
            const officerSelect = document.getElementById('officerSelect');
            const masterSelect = document.getElementById('masterSelect');
            
            const officerName = officerSelect.options[officerSelect.selectedIndex].text;
            const masterName = masterSelect.options[masterSelect.selectedIndex].text;
            
            // Verificar se pelo menos um oficial foi selecionado
            if (officerSelect.value === "" && masterSelect.value === "") {
                document.getElementById('formError').textContent = 'Selecione pelo menos um oficial de serviço.';
                document.getElementById('formError').classList.remove('hidden');
                updateButton.disabled = false;
                updateSpinner.classList.add('hidden');
                return;
            }
            
            // Preparar dados para envio
            const officerData = {
                officerName: officerSelect.value === "" ? null : officerName,
                masterName: masterSelect.value === "" ? null : masterName
            };
            
            // URL da API do Node.js
            const apiUrl = '/api/duty-officers';
            
            // Enviar requisição para a API
            fetch(apiUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Cookie': document.cookie // Enviar o cookie da sessão PHP
                },
                credentials: 'include', // Importante para enviar cookies
                body: JSON.stringify(officerData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao atualizar oficiais de serviço');
                }
                return response.json();
            })
            .then(data => {
                updateButton.disabled = false;
                updateSpinner.classList.add('hidden');
                
                if (data.success) {
                    // Atualização bem-sucedida, recarregar dados atuais
                    alert('Oficiais de serviço atualizados com sucesso!');
                    loadCurrentDutyOfficers();
                } else {
                    // Exibir mensagem de erro
                    document.getElementById('formError').textContent = data.error || 'Erro ao atualizar oficiais de serviço.';
                    document.getElementById('formError').classList.remove('hidden');
                }
            })
            .catch(error => {
                updateButton.disabled = false;
                updateSpinner.classList.add('hidden');
                document.getElementById('formError').textContent = 'Erro na comunicação com o servidor. Tente novamente.';
                document.getElementById('formError').classList.remove('hidden');
                console.error('Erro na requisição:', error);
            });
        }
    </script>
</body>
</html>