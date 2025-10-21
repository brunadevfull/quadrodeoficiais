 <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/sunset_data.php';?> 
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include $_SERVER['DOCUMENT_ROOT'].'/config/config.php';

// Verifique se o usuÃ¡rio estÃ¡ logado
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$is_portaria = isset($_SESSION['user_id']) && !$is_admin;
$username = strtolower($_SESSION['username'] ?? '');
$can_manage_duty_officers = $is_logged_in && ($is_admin || $username === 'eor');



// Recupere os postos do banco de dados
try {
    $stmt = $pdo->prepare('SELECT id, descricao, imagem FROM postos');
    $stmt->execute();
    $postos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao recuperar postos: " . $e->getMessage();
}

try {
  $stmt = $pdo->query('SELECT id, username FROM users');
  $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  echo "Erro ao recuperar usuÃ¡rios: " . $e->getMessage();
}

$officerOptions = $officerOptions ?? [];
$masterOptions = $masterOptions ?? [];

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDirectory = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$dutyOfficersApiUrl = ($scriptDirectory === '' ? '' : $scriptDirectory) . '/proxy-duty-officers.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <title>PAPEM - Quadro de Oficiais</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="../../js/jquery.min.js"></script>
    <link href="../../css/select2.min.css" rel="stylesheet" />
 <script src="../../js/temperature-bagde.js"></script>
    <script src="../../js/select2.min.js"></script>
 <script src="../../js/popper.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <!-- CSS -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/style.css" rel="stylesheet">
   
    <link rel="stylesheet" href="../../css/all.min.css">

   
    <script src="../../js/scripts.js"></script>
    <script>
    $(document).ready(function() {
        <?php if (isset($_SESSION['login_error'])): ?>
            $('#loginModal').modal('show');
        <?php endif; ?>
    });
</script>



</head>

<body>
<header id="topo">
    <div class="navbar-left">
        <img src="imagens/brasao2.png" id="papem" alt="Conecta PAPEM" class="navbar-logo">
        <div class="navbar-text">
            <h6 class="top-site-name">Marinha do Brasil</h6>
            <h1>Pagadoria de Pessoal da Marinha</h1>
            <p>"ORDEM, PRONTIDÃO E REGULARIDADE"</p>
        </div>
    </div>




 <div class="navbar-right">


<?php if ($is_logged_in): ?>
    <!-- Ações Administrativas - apenas para administradores -->
    <?php if ($is_admin): ?>
        <div class="dropdown">
            <button class="glass-button dropdown-toggle" type="button" id="adminDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Ações Administrativas
            </button>
            <div class="dropdown-menu" aria-labelledby="adminDropdown">
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#addUserModal">Adicionar Usuário</a>
                <hr>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#changePasswordModal">Redefinir Senha</a>
                <hr>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#resetUserPasswordModal">Redefinir Senha de Usuário</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($can_manage_duty_officers): ?>
        <!-- Botão Gerenciar Oficiais de Serviço - apenas para administradores e usuário EOR -->
        <button class="glass-button" data-toggle="modal" data-target="#dutyOfficersModal">
            Gerenciar Oficiais de Serviço
        </button>
    <?php endif; ?>

    <!-- Botão de Logout -->
    <button class="glass-button logout-button" onclick="window.location.href='views/logout.php'">Logout</button>
<?php else: ?>
    <button class="btn btn-primary login-button" data-toggle="modal" data-target="#loginModal">Login</button>
<?php endif; ?>
</div>


<!-- R�dio Marinha -->
<div class="radio-section">
<img class="radiomarinha" src="imagens/radio_marinha.png" alt="R�dio Marinha" >
<audio controls>
        <source src="https://stm0.inovativa.net/listen/radiomarinha/radio.mp3" type="audio/mpeg">
    
    </audio>
      
       </div>
    
        

</header>





<div class="header-bottom-area-bg">



      </div>





<?php if ($can_manage_duty_officers): ?>
<!-- Modal Gerenciar Oficiais de Serviço -->
<div class="modal fade" id="dutyOfficersModal" tabindex="-1" role="dialog" aria-labelledby="dutyOfficersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dutyOfficersModalLabel">Gerenciar Oficiais de Serviço</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="currentOfficers" class="mb-4">
                    <h6>Oficiais de Serviço Atuais</h6>
                    <div id="loadingCurrentOfficers">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                        <span class="ml-2">Carregando...</span>
                    </div>
                    <div id="officersDisplay" class="d-none">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Oficial de Serviço:</strong> <span id="currentOfficer">Não definido</span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Contramestre:</strong> <span id="currentMaster">Não definido</span></p>
                            </div>
                        </div>
                        <p class="text-muted small">Última atualização: <span id="lastUpdated">-</span></p>
                    </div>
                    <div id="errorLoadingOfficers" class="alert alert-danger d-none">
                        Erro ao carregar os oficiais de serviço atuais.
                    </div>
                </div>

                <form id="dutyOfficersForm">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="officerSelect">Oficial de Serviço:</label>
                            <select class="form-control" id="officerSelect" name="officerName">
                                <option value="">Selecione um Oficial</option>
                                <?php if (!empty($officerOptions)): ?>
                                    <?php foreach ($officerOptions as $option): ?>
                                        <?php
                                            $optionValue = $option['value'] ?? '';
                                            $optionName = $option['name'] ?? '';
                                            $optionRankDisplay = $option['rank'] ?? '';
                                            $optionShortRank = $option['short_rank'] ?? '';
                                            $optionType = $option['type'] ?? 'officer';
                                            $optionDisplay = $option['display'] ?? '';

                                            if ($optionDisplay === '') {
                                                $optionDisplay = trim(($optionRankDisplay ? $optionRankDisplay . ' ' : '') . $optionName);
                                            }

                                            if ($optionValue === '') {
                                                $optionValue = $optionName !== '' ? $optionName : $optionDisplay;
                                            }

                                            if ($optionValue === '' || $optionDisplay === '') {
                                                continue;
                                            }
                                        ?>
                                        <option
                                            value="<?php echo htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-rank="<?php echo htmlspecialchars($optionRankDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-short-rank="<?php echo htmlspecialchars($optionShortRank, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-type="<?php echo htmlspecialchars($optionType, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-display="<?php echo htmlspecialchars($optionDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-raw-name="<?php echo htmlspecialchars($optionName, ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <?php echo htmlspecialchars($optionDisplay, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Nenhum oficial disponível</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="masterSelect">Contramestre:</label>
                            <select class="form-control" id="masterSelect" name="masterName">
                                <option value="">Selecione um Contramestre</option>
                                <?php if (!empty($masterOptions)): ?>
                                    <?php foreach ($masterOptions as $option): ?>
                                        <?php
                                            $optionValue = $option['value'] ?? '';
                                            $optionName = $option['name'] ?? '';
                                            $optionRankDisplay = $option['rank'] ?? '';
                                            $optionShortRank = $option['short_rank'] ?? '';
                                            $optionType = $option['type'] ?? 'master';
                                            $optionDisplay = $option['display'] ?? '';

                                            if ($optionDisplay === '') {
                                                $optionDisplay = trim(($optionRankDisplay ? $optionRankDisplay . ' ' : '') . $optionName);
                                            }

                                            if ($optionValue === '') {
                                                $optionValue = $optionName !== '' ? $optionName : $optionDisplay;
                                            }

                                            if ($optionValue === '' || $optionDisplay === '') {
                                                continue;
                                            }
                                        ?>
                                        <option
                                            value="<?php echo htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-rank="<?php echo htmlspecialchars($optionRankDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-short-rank="<?php echo htmlspecialchars($optionShortRank, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-type="<?php echo htmlspecialchars($optionType, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-display="<?php echo htmlspecialchars($optionDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-raw-name="<?php echo htmlspecialchars($optionName, ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <?php echo htmlspecialchars($optionDisplay, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Nenhum contramestre disponível</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> Os oficiais de serviço definidos serão exibidos na barra superior do sistema PAPEM. A atualização é imediata.
                    </div>

                    <div id="formError" class="alert alert-danger d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="updateOfficersButton" class="btn btn-primary">
                    <span id="updateSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    Atualizar Oficiais de Serviço
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>









<!-- Modal de Login -->
<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">Login</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <?php if (isset($_SESSION['login_error'])): ?>
          <div class="alert alert-danger">
            <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
          </div>
        <?php endif; ?>
        <form action="views/process_login.php" method="POST">
          <div class="form-group">
            <label for="username">Usuário:</label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="form-group">
            <label for="password">Senha:</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- Modal Adicionar UsuÃ¡rio -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Adicionar Usuário</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addUserForm" action="views/process_add_user.php" method="POST">
                    <div class="form-group">
                        <label for="username">Usuário:</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div id="usernameError" class="text-danger"></div>
                    </div>
                    <div class="form-group">
                        <label for="password">Senha:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="is_admin">Administrador:</label>
                        <input type="checkbox" id="is_admin" name="is_admin">
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de sucesso ao adicionar usuário -->
<div class="modal fade" id="userAddSuccessModal" tabindex="-1" role="dialog" aria-labelledby="userAddSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userAddSuccessModalLabel">Sucesso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Usuário adicionado com sucesso!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal de Erro ao adicionar usuário -->
<div id="userAddErrorModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="userAddErrorModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userAddErrorModalLabel">Erro</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="userAddErrorMessage">Usuário ja existe!</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>


<!-- Modal de Erro -->
<div id="passwordErrorModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Erro</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      <p id="passwordErrorMessage">A nova senha e a confirmação não coincidem.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>


<!-- Modal de Sucesso -->
<div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Sucesso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Usuário adicionado com sucesso!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Ok</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Redefinir Senha -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changePasswordModalLabel">Redefinir Senha</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="changePasswordForm" action="views/process_change_password.php" method="POST">
          <div class="form-group">
            <label for="current_password">Senha Atual:</label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
          </div>
          <div class="form-group">
            <label for="new_password">Nova Senha:</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirmar Nova Senha:</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
          </div>
          <button type="submit" class="btn btn-primary">Redefinir Senha</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Sucesso para RedefiniÃ§Ã£o de Senha -->
<div class="modal fade" id="passwordChangeSuccessModal" tabindex="-1" role="dialog" aria-labelledby="passwordChangeSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordChangeSuccessModalLabel">Sucesso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Senha redefinida com sucesso!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Ok</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Erro -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">Erro</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo isset($_SESSION['error']) ? $_SESSION['error'] : ''; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Redefinir Senha de UsuÃ¡rio -->
<div class="modal fade" id="resetUserPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetUserPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetUserPasswordModalLabel">Redefinir Senha de Usuário</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="resetUserPasswordForm" action="views/process_reset_user_password.php" method="POST">
                    <div class="form-group">
                        <label for="reset_username">Usuário:</label>
                        <select class="form-control" id="reset_username" name="username" required>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>"><?php echo $usuario['username']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reset_new_password">Nova Senha:</label>
                        <input type="password" class="form-control" id="reset_new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="reset_confirm_password">Confirmar Nova Senha:</label>
                        <input type="password" class="form-control" id="reset_confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Redefinir Senha</button>
                </form>
            </div>
        </div>
    </div>

</div>
<!-- Modal de Sucesso para Redefinição de Senha do Usuário -->
<div class="modal fade" id="resetPasswordSuccessModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordSuccessModalLabel">Sucesso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Senha do usuário redefinida com sucesso!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Ok</button>
            </div>
        </div>
    </div>
</div>

<script>
const dutyOfficersApiUrl = <?php echo json_encode($dutyOfficersApiUrl, JSON_UNESCAPED_SLASHES); ?>;

$(document).ready(function() {
    $('#dutyOfficersModal').on('shown.bs.modal', function() {
        loadCurrentDutyOfficers();
    });

    $('#updateOfficersButton').on('click', function() {
        updateDutyOfficers();
    });
});

function formatDutyDisplay(name, rank) {
    const normalizedName = typeof name === 'string' ? name.trim() : '';
    const normalizedRank = typeof rank === 'string' ? rank.trim() : '';

    if (normalizedName === '' && normalizedRank === '') {
        return 'Não definido';
    }

    if (normalizedName === '') {
        return normalizedRank;
    }

    if (normalizedRank === '') {
        return normalizedName;
    }

    if (normalizedName.toUpperCase().startsWith(normalizedRank.toUpperCase())) {
        return normalizedName;
    }

    return `${normalizedRank} ${normalizedName}`.trim();
}

// Função para carregar os oficiais de serviço atuais
function loadCurrentDutyOfficers() {
    $('#loadingCurrentOfficers').removeClass('d-none');
    $('#officersDisplay').addClass('d-none');
    $('#errorLoadingOfficers').addClass('d-none');
    
    // Usar o proxy PHP no mesmo domínio
    fetch(dutyOfficersApiUrl, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro ao carregar oficiais de serviço');
        }
        return response.json();
    })
    .then(data => {
        $('#loadingCurrentOfficers').addClass('d-none');
        
        if (data.success) {
            $('#officersDisplay').removeClass('d-none');

            const officerName = data.officers?.officerName ?? null;
            const officerRank = data.officers?.officerRank ?? null;
            const officerDisplayName = data.officers?.officerDisplayName ?? officerName;
            const masterName = data.officers?.masterName ?? null;
            const masterRank = data.officers?.masterRank ?? null;
            const masterDisplayName = data.officers?.masterDisplayName ?? masterName;

            // Atualizar os dados na interface
            $('#currentOfficer').text(formatDutyDisplay(officerDisplayName, officerRank));
            $('#currentMaster').text(formatDutyDisplay(masterDisplayName, masterRank));
            
            // Formatar data de atualização
            const updatedDate = data.officers?.updatedAt ? new Date(data.officers.updatedAt) : null;
            $('#lastUpdated').text(updatedDate ? updatedDate.toLocaleString('pt-BR') : '-');
            
            // Pré-selecionar os valores nos dropdowns, se disponíveis
            if (data.officers?.officerName || data.officers?.officerRank) {
                selectOptionByValue('officerSelect', data.officers.officerName, data.officers.officerRank);
            }

            if (data.officers?.masterName || data.officers?.masterRank) {
                selectOptionByValue('masterSelect', data.officers.masterName, data.officers.masterRank);
            }
        } else {
            $('#errorLoadingOfficers').removeClass('d-none');
            console.error('Erro ao carregar dados:', data.error);
        }
    })
    .catch(error => {
        $('#loadingCurrentOfficers').addClass('d-none');
        $('#errorLoadingOfficers').removeClass('d-none');
        console.error('Erro na requisição:', error);
    });
}

// Função para atualizar os oficiais de serviço
function updateDutyOfficers() {
    // Limpar mensagens de erro anteriores
    $('#formError').addClass('d-none');
    
    // Mostrar spinner de carregamento
    const updateButton = $('#updateOfficersButton');
    const updateSpinner = $('#updateSpinner');
    updateButton.prop('disabled', true);
    updateSpinner.removeClass('d-none');
    
    // Obter valores dos campos
    const officerSelect = document.getElementById('officerSelect');
    const masterSelect = document.getElementById('masterSelect');

    const officerOption = officerSelect?.options[officerSelect.selectedIndex] ?? null;
    const masterOption = masterSelect?.options[masterSelect.selectedIndex] ?? null;

    const officerName = officerOption ? officerOption.value : '';
    const officerRank = officerOption ? officerOption.dataset.rank || null : null;
    const masterName = masterOption ? masterOption.value : '';
    const masterRank = masterOption ? masterOption.dataset.rank || null : null;
    
    // Verificar se pelo menos um oficial foi selecionado
    if (officerName === "" && masterName === "") {
        $('#formError').text('Selecione pelo menos um oficial de serviço.');
        $('#formError').removeClass('d-none');
        updateButton.prop('disabled', false);
        updateSpinner.addClass('d-none');
        return;
    }

    // Preparar dados para envio
    const officerData = {
        officerName: officerName === "" ? null : officerName,
        officerRank: officerRank === null || officerRank === '' ? null : officerRank,
        masterName: masterName === "" ? null : masterName,
        masterRank: masterRank === null || masterRank === '' ? null : masterRank,
    };
    
    // Usar o proxy PHP no mesmo domínio

    // Enviar requisição para a API
    fetch(dutyOfficersApiUrl, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(officerData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro ao atualizar oficiais de serviço');
        }
        return response.json();
    })
    .then(data => {
        updateButton.prop('disabled', false);
        updateSpinner.addClass('d-none');
        
        if (data.success) {
            // Mostrar alerta de sucesso
            alert('Oficiais de serviço atualizados com sucesso!');
            
            // Atualização bem-sucedida, recarregar dados atuais
            loadCurrentDutyOfficers();
        } else {
            // Exibir mensagem de erro
            $('#formError').text(data.error || 'Erro ao atualizar oficiais de serviço.');
            $('#formError').removeClass('d-none');
        }
    })
    .catch(error => {
        updateButton.prop('disabled', false);
        updateSpinner.addClass('d-none');
        $('#formError').text('Erro na comunicação com o servidor. Tente novamente.');
        $('#formError').removeClass('d-none');
        console.error('Erro na requisição:', error);
    });
}

// Função para selecionar uma opção no dropdown pelo valor
function selectOptionByValue(selectId, value, rank = null) {
    const selectElement = document.getElementById(selectId);
    if (!selectElement) {
        return;
    }

    const normalizedValue = typeof value === 'string' ? value.trim() : '';
    const normalizedRank = typeof rank === 'string' ? rank.trim() : '';

    if (normalizedValue === '' && normalizedRank === '') {
        $(selectElement).val('').trigger('change');
        return;
    }

    for (let i = 0; i < selectElement.options.length; i++) {
        const option = selectElement.options[i];
        const optionValue = option.value?.trim?.() ?? '';
        const optionDisplay = option.dataset.display?.trim?.() ?? '';
        const optionRawName = option.dataset.rawName?.trim?.() ?? '';
        const optionRank = option.dataset.rank?.trim?.() ?? '';

        const matchesValue = normalizedValue !== '' && (
            optionValue === normalizedValue ||
            optionDisplay === normalizedValue ||
            optionRawName === normalizedValue
        );

        const matchesRank = normalizedRank !== '' && optionRank === normalizedRank;

        if (matchesValue || matchesRank) {
            $(selectElement).val(option.value).trigger('change');
            return;
        }
    }
}
</script>


</body>
</html>
