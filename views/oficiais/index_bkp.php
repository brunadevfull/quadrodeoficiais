<?php
include 'views/partials/header.php';

// Caminho do arquivo temporÃ¡rio
$tempFile = sys_get_temp_dir() . '/oficiais_status.json';



if (file_exists($tempFile)) {
    $statusData = json_decode(file_get_contents($tempFile), true);
    if ($statusData === null) {
        $statusData = []; // Inicializa como um array vazio se a decodificaÃ§Ã£o falhar
    }
} else {
    $statusData = [];
}



?>
<script>
$(document).ready(function() {
    <?php if (isset($_SESSION['password_change_success'])): ?>
        $('#passwordChangeSuccessModal').modal('show');
        <?php unset($_SESSION['password_change_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        $('#errorModal').modal('show');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

   

    <?php if (isset($_SESSION['login_error'])): ?>
        $('#loginModal').modal('show');
        <?php unset($_SESSION['login_error']); ?>
    <?php endif; ?>
    $(document).ready(function() {
    // Exemplo para mostrar o modal de erro ao adicionar usuário
    <?php if (isset($_SESSION['user_add_error'])): ?>
        $('#userAddErrorModal').modal('show');
        <?php unset($_SESSION['user_add_error']); ?>
    <?php endif; ?>
});
});
</script>
<script>
    // Define a variável global no JavaScript com base no PHP
    var userIsAuthenticated = <?php echo json_encode($is_logged_in); ?>;
</script>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
   
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

   
    <title>Quadro de Oficiais</title>
</head>
<body class="<?php echo $is_logged_in ? 'logged-in' : 'logged-out'; ?> <?php echo $is_portaria ? 'portaria' : ''; ?>">
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var select = document.getElementById('posto');
    var options = select.options;

    for (var i = 0; i < options.length; i++) {
        var imgSrc = options[i].getAttribute('data-img');
        if (imgSrc) {
            options[i].style.backgroundImage = 'url(' + imgSrc + ')';
        }
    }
});

  </script>

<div class="quadro-de-oficiais">
  QUADRO DE OFICIAIS
  <div id="datetime" class="datetime"></div>
</div>
<div class="container">
  <div class="row">
    
    <?php
    if (!isset($is_logged_in)) {
        $is_logged_in = false;
    }

$oficiaisRM1 = array_filter($oficiais, function($oficial) {
    return stripos($oficial['descricao'], 'RM1') !== false;
});

// Oficiais sem 'RM1' na descri��o
$oficiaisSemRM1 = array_filter($oficiais, function($oficial) {
    return stripos($oficial['descricao'], 'RM1') === false;
});

$numOficiaisSemRM1 = count($oficiaisSemRM1);

// Limites das colunas
$numOficiaisColuna1 = min(13, $numOficiaisSemRM1); 
$numOficiaisColuna2 = min(12, max(0, $numOficiaisSemRM1 - $numOficiaisColuna1)); 
$numOficiaisColuna3 = count($oficiaisRM1); // Apenas oficiais com RM1

for ($i = 0; $i < 3; $i++): ?>
  <div class="col-md-4">
    <table class="table table-bordered">
      <thead>
        <tr>
          <?php if ($is_admin):  ?>
            <th class="location-cell">POSIÇÕES</th>
          <?php endif; ?>
          <th>POSTO/NOME</th>
          <th class="text-center status-cell"><span class="status-label green">A BORDO</span></th>
          <?php if ($is_admin): ?>
            <th class="text-center">AÇÕES</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $start = 0;
        $end = 0;
    
        if ($i == 0) {
          // Coluna 1: at� 13 oficiais sem RM1
          $start = 0;
          $end = $numOficiaisColuna1;
          $oficiaisParaExibir = array_slice($oficiaisSemRM1, $start, $end);
        } elseif ($i == 1) {
          // Coluna 2: at� 12 oficiais sem RM1
          $start = $numOficiaisColuna1;
          $end = $numOficiaisColuna1 + $numOficiaisColuna2;
          $oficiaisParaExibir = array_slice($oficiaisSemRM1, $start, $numOficiaisColuna2);
        } else {
          // Coluna 3: apenas oficiais com RM1
          $oficiaisParaExibir = $oficiaisRM1;
        }
    
        foreach ($oficiaisParaExibir as $oficial):
          $status = isset($statusData[$oficial['id']]) ? $statusData[$oficial['id']] : $oficial['status'];
          $checked = $status === 'bordo' ? 'checked' : '';
          ?>
          <tr id='row-<?php echo $oficial['localizacao']; ?>' class='<?php echo $status === 'bordo' ? "present" : "absent"; ?>'>
            <?php if ($is_admin):  ?>
              <td class='location-cell'><?php echo $oficial['localizacao']; ?></td>
            <?php endif; ?>
            <td class='name-column'><img src='<?php echo $oficial['imagem']; ?>' width='80px'> <?php echo $oficial['descricao'] . ' ' . $oficial['nome']; ?></td>
            <td class='text-center status-cell'>
              <label class='switch'>
                <input type='checkbox' data-id='<?php echo $oficial['id']; ?>' <?php echo $checked; ?> onclick='togglePresence(<?php echo $oficial['id']; ?>)'>
                <span class='slider round'></span>
              </label>
            </td>
            <?php if ($is_admin): ?>
              <td class='text-center'>
                <button class='btn btn-add' onclick='abrirModalAdicionar(<?php echo $oficial['localizacao']; ?>)'>+</button>
                <button class='btn btn-danger' onclick='window.excluirOficial(<?php echo $oficial['id']; ?>)'>Excluir</button>
                <button class='btn btn-primary' onclick='abrirModalEditar(<?php echo $oficial['id']; ?>, "<?php echo $oficial['nome']; ?>",
                <?php echo $oficial['posto_id']; ?>, 
                "<?php echo $status; ?>",
                 <?php echo $oficial['localizacao']; ?>)'>Editar</button>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endfor; ?>
  </div>
</div>
<?php

?>

  <!-- <?php if ($is_logged_in): ?>
    <a href="#" class="btn btn-success" onclick="abrirModalAdicionar()">Adicionar Oficial</a>
  <?php endif; ?> -->
</div>

<!-- Modal Adicionar Oficial -->
<div id="addOfficialModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="addOfficialForm" method="POST" action="views/process_add_official.php">
        <div class="modal-header">
          <h5 class="modal-title">Adicionar Oficial</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p id="addOfficialMessage" class="text-info"></p>
          <div class="form-group">
            <label for="nome">Nome:</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
          </div>

          <div class="form-group">
  <label for="posto">Posto:</label>
  <select class="form-control" id="posto" name="posto" required>
    <option value="">Selecione um posto</option>
    <?php foreach ($postos as $posto): ?>
      <option value="<?php echo $posto['id']; ?>" data-img="<?php echo $posto['imagem']; ?>"><?php echo $posto['descricao']; ?></option>    <?php endforeach; ?>
  </select>
</div>
          <div class="form-group">
            <label for="status">Status:</label>
            <select class="form-control" id="status" name="status" required>
              <option value="bordo">A Bordo</option>
              <option value="terra">Em Terra</option>
            </select>
          </div>
          <div class="form-group">
            <label for="localizacao">Localização:</label>
            <input type="number" class="form-control" id="localizacao" name="localizacao" required readonly>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
          <button type="submit" class="btn btn-primary">Adicionar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Editar Oficial -->
<div id="editOfficialModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="editOfficialForm" method="POST" action="views/process_edit_official.php">
        <div class="modal-header">
          <h5 class="modal-title">Editar Oficial</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_id" name="id">
          <div class="form-group">
            <label for="edit_nome">Nome:</label>
            <input type="text" class="form-control" id="edit_nome" name="nome" required>
          </div>
          <div class="form-group">
            <label for="edit_posto">Posto:</label>
            <select class="form-control" id="edit_posto" name="posto" required>
              <option value="">Selecione um posto</option>
              <?php foreach ($postos as $posto): ?>
                <option value="<?php echo $posto['id']; ?>"><?php echo $posto['descricao']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>


          
          <div class="form-group">
            <label for="edit_status">Status:</label>
            <select class="form-control" id="edit_status" name="status" required>
              <option value="bordo">A Bordo</option>
              <option value="terra">Em Terra</option>
            </select>
          </div>

          
          <div class="form-group">



            <label for="edit_localizacao">Localização:</label>
            <input type="number" class="form-control" id="edit_localizacao" name="localizacao" required readonly>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

 <!-- Modal de Sucesso do Oficial -->
<div class="modal fade" id="oficialSuccessModal" tabindex="-1" role="dialog" aria-labelledby="oficialSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="oficialSuccessModalLabel">Sucesso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Oficial adicionado com sucesso!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Ok</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Sucesso da EdiÃ§Ã£o -->
<div class="modal fade" id="editSuccessModal" tabindex="-1" role="dialog" aria-labelledby="editSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSuccessModalLabel">Sucesso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Oficial editado com sucesso!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Ok</button>
            </div>
        </div>
    </div>
</div>




<?php
include 'views/partials/footer.php';
?>
</body>
</html>

