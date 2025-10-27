import './styles.css';
import {
  createOfficer,
  deleteOfficer,
  DutyAssignment,
  fetchDutyOfficers,
  fetchOfficers,
  fetchPosts,
  OfficerRecord,
  PostRecord,
  updateDutyAssignment,
  updateOfficer
} from './api';

type ModalMode = 'create' | 'edit';

type AppState = {
  officers: OfficerRecord[];
  posts: PostRecord[];
  duty: DutyAssignment | null;
  modalMode: ModalMode;
  editingOfficerId: number | null;
};

const state: AppState = {
  officers: [],
  posts: [],
  duty: null,
  modalMode: 'create',
  editingOfficerId: null
};

const elements = {
  refreshButton: document.getElementById('refreshButton') as HTMLButtonElement | null,
  addOfficerButton: document.getElementById('addOfficerButton') as HTMLButtonElement | null,
  dutyForm: document.getElementById('dutyForm') as HTMLFormElement | null,
  dutyOfficerSelect: document.getElementById('dutyOfficerSelect') as HTMLSelectElement | null,
  dutyMasterSelect: document.getElementById('dutyMasterSelect') as HTMLSelectElement | null,
  dutyOfficerDisplay: document.getElementById('dutyOfficerDisplay') as HTMLSpanElement | null,
  dutyMasterDisplay: document.getElementById('dutyMasterDisplay') as HTMLSpanElement | null,
  dutyUpdatedAt: document.getElementById('dutyUpdatedAt') as HTMLSpanElement | null,
  clearDutyButton: document.getElementById('clearDutyButton') as HTMLButtonElement | null,
  officerGrid: document.getElementById('officerGrid') as HTMLElement | null,
  appMessage: document.getElementById('appMessage') as HTMLElement | null,
  loadingIndicator: document.getElementById('loadingIndicator') as HTMLElement | null,
  modalOverlay: document.getElementById('modalOverlay') as HTMLElement | null,
  officerModal: document.getElementById('officerModal') as HTMLElement | null,
  officerForm: document.getElementById('officerForm') as HTMLFormElement | null,
  officerModalTitle: document.getElementById('officerModalTitle') as HTMLElement | null,
  officerId: document.getElementById('officerId') as HTMLInputElement | null,
  officerName: document.getElementById('officerName') as HTMLInputElement | null,
  officerPost: document.getElementById('officerPost') as HTMLSelectElement | null,
  officerStatus: document.getElementById('officerStatus') as HTMLSelectElement | null,
  officerLocation: document.getElementById('officerLocation') as HTMLInputElement | null,
  closeModalButton: document.getElementById('closeModalButton') as HTMLButtonElement | null,
  cancelModalButton: document.getElementById('cancelModalButton') as HTMLButtonElement | null,
  modalError: document.getElementById('modalError') as HTMLElement | null,
  toast: document.getElementById('toast') as HTMLElement | null
};

let toastTimeout: number | null = null;

const setLoading = (loading: boolean) => {
  if (!elements.loadingIndicator) {
    return;
  }

  elements.loadingIndicator.classList.toggle('hidden', !loading);
};

const showMessage = (message: string, type: 'error' | 'info' = 'info') => {
  if (!elements.appMessage) {
    return;
  }

  elements.appMessage.textContent = message;
  elements.appMessage.classList.remove('hidden', 'error', 'info');
  elements.appMessage.classList.add(type);
};

const clearMessage = () => {
  if (!elements.appMessage) {
    return;
  }

  elements.appMessage.textContent = '';
  elements.appMessage.classList.add('hidden');
  elements.appMessage.classList.remove('error', 'info');
};

const showToast = (message: string, type: 'success' | 'error' = 'success') => {
  if (!elements.toast) {
    return;
  }

  elements.toast.textContent = message;
  elements.toast.classList.remove('hidden', 'success', 'error');
  elements.toast.classList.add(type);

  if (toastTimeout) {
    window.clearTimeout(toastTimeout);
  }

  toastTimeout = window.setTimeout(() => {
    elements.toast?.classList.add('hidden');
  }, 3200);
};

const hideToast = () => {
  if (!elements.toast) {
    return;
  }

  elements.toast.classList.add('hidden');
  if (toastTimeout) {
    window.clearTimeout(toastTimeout);
    toastTimeout = null;
  }
};

const formatDutyDisplay = (name: string | null, rank: string | null): string => {
  const normalizedName = (name ?? '').trim();
  const normalizedRank = (rank ?? '').trim();

  if (!normalizedName && !normalizedRank) {
    return '-';
  }

  if (!normalizedRank) {
    return normalizedName;
  }

  if (!normalizedName) {
    return normalizedRank;
  }

  if (normalizedName.toUpperCase().startsWith(normalizedRank.toUpperCase())) {
    return normalizedName;
  }

  return `${normalizedRank} ${normalizedName}`.trim();
};

const formatDateTime = (value: string | null): string => {
  if (!value) {
    return '-';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short'
  }).format(date);
};

const renderDutySummary = () => {
  if (!elements.dutyOfficerDisplay || !elements.dutyMasterDisplay || !elements.dutyUpdatedAt) {
    return;
  }

  const duty = state.duty;

  if (!duty) {
    elements.dutyOfficerDisplay.textContent = '-';
    elements.dutyMasterDisplay.textContent = '-';
    elements.dutyUpdatedAt.textContent = '-';
    return;
  }

  elements.dutyOfficerDisplay.textContent = formatDutyDisplay(duty.officerDisplayName ?? duty.officerName, duty.officerRank);
  elements.dutyMasterDisplay.textContent = formatDutyDisplay(duty.masterDisplayName ?? duty.masterName, duty.masterRank);
  elements.dutyUpdatedAt.textContent = formatDateTime(duty.updatedAt);
};

const renderDutyOptions = () => {
  if (!elements.dutyOfficerSelect || !elements.dutyMasterSelect) {
    return;
  }

  const renderSelect = (select: HTMLSelectElement) => {
    const currentValue = select.value;
    select.innerHTML = '<option value="">Selecione</option>';

    const sorted = [...state.officers].sort((a, b) => a.localizacao - b.localizacao);
    for (const officer of sorted) {
      const option = document.createElement('option');
      option.value = officer.id.toString();
      option.textContent = `${officer.descricao} ${officer.nome}`.trim();
      select.appendChild(option);
    }

    const hasValue = [...select.options].some((option) => option.value === currentValue);
    if (hasValue) {
      select.value = currentValue;
    }
  };

  renderSelect(elements.dutyOfficerSelect);
  renderSelect(elements.dutyMasterSelect);
};

const renderPostOptions = () => {
  if (!elements.officerPost) {
    return;
  }

  const currentValue = elements.officerPost.value;
  elements.officerPost.innerHTML = '<option value="">Selecione um posto</option>';

  const sortedPosts = [...state.posts].sort((a, b) => a.descricao.localeCompare(b.descricao));

  for (const post of sortedPosts) {
    const option = document.createElement('option');
    option.value = post.id.toString();
    option.textContent = post.descricao;
    elements.officerPost.appendChild(option);
  }

  if (currentValue) {
    elements.officerPost.value = currentValue;
  }
};

const closeModal = () => {
  elements.modalOverlay?.classList.add('hidden');
  elements.officerModal?.classList.add('hidden');
  hideToast();
  state.modalMode = 'create';
  state.editingOfficerId = null;
  if (elements.officerForm) {
    elements.officerForm.reset();
  }
  if (elements.modalError) {
    elements.modalError.textContent = '';
  }
};

const openModal = (mode: ModalMode, officer?: OfficerRecord) => {
  state.modalMode = mode;
  state.editingOfficerId = officer ? officer.id : null;

  if (elements.officerModalTitle) {
    elements.officerModalTitle.textContent = mode === 'create' ? 'Adicionar oficial' : 'Editar oficial';
  }

  if (elements.modalError) {
    elements.modalError.textContent = '';
  }

  renderPostOptions();

  if (mode === 'create') {
    elements.officerForm?.reset();
    if (elements.officerLocation) {
      const suggested = state.officers.length + 1;
      elements.officerLocation.value = String(suggested);
    }
  }

  if (mode === 'edit' && officer) {
    if (elements.officerId) {
      elements.officerId.value = officer.id.toString();
    }
    if (elements.officerName) {
      elements.officerName.value = officer.nome;
    }
    if (elements.officerPost) {
      elements.officerPost.value = officer.postoId.toString();
    }
    if (elements.officerStatus) {
      elements.officerStatus.value = officer.status;
    }
    if (elements.officerLocation) {
      elements.officerLocation.value = officer.localizacao.toString();
    }
  }

  elements.modalOverlay?.classList.remove('hidden');
  elements.officerModal?.classList.remove('hidden');
  elements.officerName?.focus();
};

const findOfficerById = (id: number): OfficerRecord | undefined => {
  return state.officers.find((officer) => officer.id === id);
};

const updateCardStatus = (card: HTMLElement, officer: OfficerRecord) => {
  card.classList.remove('present', 'absent');
  card.classList.add(officer.status === 'bordo' ? 'present' : 'absent');

  const statusLabel = card.querySelector<HTMLElement>('.status-label');
  if (statusLabel) {
    statusLabel.textContent = officer.status === 'bordo' ? 'A bordo' : 'Em terra';
  }
};

const createOfficerCard = (officer: OfficerRecord): HTMLElement => {
  const card = document.createElement('article');
  card.className = `officer-card ${officer.status === 'bordo' ? 'present' : 'absent'}`;
  card.dataset.id = officer.id.toString();

  const header = document.createElement('div');
  header.className = 'officer-card__header';

  if (officer.imagem) {
    const image = document.createElement('img');
    image.src = officer.imagem;
    image.alt = officer.descricao;
    image.className = 'officer-card__avatar';
    header.appendChild(image);
  }

  const headerText = document.createElement('div');
  headerText.className = 'officer-card__title';

  const rank = document.createElement('p');
  rank.className = 'officer-card__rank';
  rank.textContent = officer.descricao;
  headerText.appendChild(rank);

  const name = document.createElement('h3');
  name.textContent = officer.nome;
  headerText.appendChild(name);

  header.appendChild(headerText);
  card.appendChild(header);

  const statusContainer = document.createElement('div');
  statusContainer.className = 'officer-card__status';

  const toggleLabel = document.createElement('label');
  toggleLabel.className = 'toggle';

  const toggleInput = document.createElement('input');
  toggleInput.type = 'checkbox';
  toggleInput.checked = officer.status === 'bordo';
  toggleInput.className = 'toggle__input';
  toggleInput.setAttribute('aria-label', `Alterar status de ${officer.nome}`);

  const slider = document.createElement('span');
  slider.className = 'toggle__slider';

  const statusLabel = document.createElement('span');
  statusLabel.className = 'status-label';
  statusLabel.textContent = officer.status === 'bordo' ? 'A bordo' : 'Em terra';

  toggleLabel.append(toggleInput, slider);
  statusContainer.append(toggleLabel, statusLabel);
  card.appendChild(statusContainer);

  const actions = document.createElement('div');
  actions.className = 'officer-card__actions';

  const editButton = document.createElement('button');
  editButton.type = 'button';
  editButton.className = 'button secondary';
  editButton.textContent = 'Editar';

  const deleteButton = document.createElement('button');
  deleteButton.type = 'button';
  deleteButton.className = 'button danger';
  deleteButton.textContent = 'Excluir';

  actions.append(editButton, deleteButton);
  card.appendChild(actions);

  toggleInput.addEventListener('change', async () => {
    const newStatus: OfficerRecord['status'] = toggleInput.checked ? 'bordo' : 'terra';
    toggleInput.disabled = true;

    try {
      await updateOfficer({
        id: officer.id,
        nome: officer.nome,
        postoId: officer.postoId,
        status: newStatus,
        localizacao: officer.localizacao
      });
      officer.status = newStatus;
      updateCardStatus(card, officer);
      showToast('Status atualizado com sucesso.');
    } catch (error) {
      toggleInput.checked = officer.status === 'bordo';
      showToast(error instanceof Error ? error.message : 'Falha ao atualizar status.', 'error');
    } finally {
      toggleInput.disabled = false;
    }
  });

  editButton.addEventListener('click', () => {
    openModal('edit', officer);
  });

  deleteButton.addEventListener('click', async () => {
    const confirmed = window.confirm(`Deseja remover ${officer.nome}?`);
    if (!confirmed) {
      return;
    }

    try {
      await deleteOfficer(officer.id);
      showToast('Oficial removido com sucesso.');
      await loadOfficers();
      renderDutyOptions();
    } catch (error) {
      showToast(error instanceof Error ? error.message : 'Falha ao remover oficial.', 'error');
    }
  });

  return card;
};

const renderOfficers = () => {
  if (!elements.officerGrid) {
    return;
  }

  elements.officerGrid.innerHTML = '';

  if (state.officers.length === 0) {
    const emptyState = document.createElement('p');
    emptyState.className = 'empty-state';
    emptyState.textContent = 'Nenhum oficial cadastrado.';
    elements.officerGrid.appendChild(emptyState);
    return;
  }

  const sorted = [...state.officers].sort((a, b) => a.localizacao - b.localizacao);
  const columns = Math.min(3, Math.max(1, sorted.length));
  const containers: HTMLElement[] = [];

  for (let i = 0; i < columns; i += 1) {
    const column = document.createElement('div');
    column.className = 'officer-column';
    containers.push(column);
    elements.officerGrid.appendChild(column);
  }

  sorted.forEach((officer, index) => {
    const columnIndex = index % columns;
    const card = createOfficerCard(officer);
    containers[columnIndex].appendChild(card);
  });
};

const loadOfficers = async () => {
  try {
    setLoading(true);
    const officers = await fetchOfficers();
    state.officers = officers;
    renderOfficers();
    renderDutyOptions();
    clearMessage();
  } catch (error) {
    showMessage(error instanceof Error ? error.message : 'Falha ao carregar oficiais.', 'error');
  } finally {
    setLoading(false);
  }
};

const loadPosts = async () => {
  try {
    const posts = await fetchPosts();
    state.posts = posts;
    renderPostOptions();
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Falha ao carregar postos.', 'error');
  }
};

const loadDutyAssignment = async () => {
  try {
    const duty = await fetchDutyOfficers();
    state.duty = duty;
    renderDutySummary();
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Falha ao carregar oficiais de serviço.', 'error');
  }
};

const handleDutySubmit = async (event: SubmitEvent) => {
  event.preventDefault();

  if (!elements.dutyOfficerSelect || !elements.dutyMasterSelect) {
    return;
  }

  const officerId = elements.dutyOfficerSelect.value ? Number(elements.dutyOfficerSelect.value) : null;
  const masterId = elements.dutyMasterSelect.value ? Number(elements.dutyMasterSelect.value) : null;

  const officer = officerId ? findOfficerById(officerId) : undefined;
  const master = masterId ? findOfficerById(masterId) : undefined;

  if (!officer && !master) {
    showToast('Selecione pelo menos um oficial ou contramestre.', 'error');
    return;
  }

  try {
    await updateDutyAssignment({
      officerName: officer ? officer.nome : null,
      officerRank: officer ? officer.descricao : null,
      masterName: master ? master.nome : null,
      masterRank: master ? master.descricao : null
    });
    showToast('Oficiais de serviço atualizados.');
    await loadDutyAssignment();
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Falha ao salvar oficiais de serviço.', 'error');
  }
};

const handleModalSubmit = async (event: SubmitEvent) => {
  event.preventDefault();

  if (!elements.officerForm || !elements.officerName || !elements.officerPost || !elements.officerStatus || !elements.officerLocation) {
    return;
  }

  const form = elements.officerForm;
  const formData = new FormData(form);

  const nome = (formData.get('nome') ?? '').toString().trim();
  const postoId = Number(formData.get('postoId'));
  const status = (formData.get('status') ?? 'terra').toString() as OfficerRecord['status'];
  const localizacao = Number(formData.get('localizacao'));

  if (!nome || Number.isNaN(postoId) || postoId <= 0 || Number.isNaN(localizacao) || localizacao <= 0) {
    if (elements.modalError) {
      elements.modalError.textContent = 'Preencha todos os campos obrigatórios.';
    }
    return;
  }

  try {
    if (state.modalMode === 'create') {
      await createOfficer({ nome, postoId, status, localizacao });
      showToast('Oficial adicionado com sucesso.');
    } else {
      const officerId = state.editingOfficerId;
      if (!officerId) {
        throw new Error('Identificador do oficial não encontrado.');
      }
      await updateOfficer({ id: officerId, nome, postoId, status, localizacao });
      showToast('Oficial atualizado com sucesso.');
    }

    closeModal();
    await loadOfficers();
    renderDutyOptions();
  } catch (error) {
    if (elements.modalError) {
      elements.modalError.textContent = error instanceof Error ? error.message : 'Falha ao salvar oficial.';
    }
  }
};

const initializeEvents = () => {
  elements.refreshButton?.addEventListener('click', async () => {
    await Promise.all([loadOfficers(), loadDutyAssignment()]);
  });

  elements.addOfficerButton?.addEventListener('click', () => {
    openModal('create');
  });

  elements.closeModalButton?.addEventListener('click', () => {
    closeModal();
  });

  elements.cancelModalButton?.addEventListener('click', () => {
    closeModal();
  });

  elements.modalOverlay?.addEventListener('click', () => {
    closeModal();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !elements.officerModal?.classList.contains('hidden')) {
      closeModal();
    }
  });

  elements.dutyForm?.addEventListener('submit', handleDutySubmit);

  elements.clearDutyButton?.addEventListener('click', () => {
    if (elements.dutyOfficerSelect) {
      elements.dutyOfficerSelect.value = '';
    }
    if (elements.dutyMasterSelect) {
      elements.dutyMasterSelect.value = '';
    }
  });

  elements.officerForm?.addEventListener('submit', handleModalSubmit);
};

const bootstrap = async () => {
  initializeEvents();
  setLoading(true);

  await Promise.all([loadPosts(), loadOfficers(), loadDutyAssignment()]);

  setLoading(false);
};

void bootstrap();
