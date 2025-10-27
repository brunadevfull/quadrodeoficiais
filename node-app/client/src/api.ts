export interface OfficerRecord {
  id: number;
  nome: string;
  descricao: string;
  imagem: string | null;
  status: 'bordo' | 'terra';
  localizacao: number;
  postoId: number;
}

export interface PostRecord {
  id: number;
  descricao: string;
  imagem: string | null;
}

export interface DutyAssignment {
  officerName: string | null;
  officerRank: string | null;
  officerDisplayName: string | null;
  masterName: string | null;
  masterRank: string | null;
  masterDisplayName: string | null;
  updatedAt: string | null;
}

interface SuccessResponse {
  success: boolean;
  [key: string]: unknown;
}

const isJsonResponse = (response: Response): boolean => {
  const contentType = response.headers.get('content-type');
  return Boolean(contentType && contentType.includes('application/json'));
};

const parseJson = async <T>(response: Response): Promise<T | null> => {
  if (!isJsonResponse(response)) {
    return null;
  }

  return (await response.json()) as T;
};

const request = async <T>(input: RequestInfo, init?: RequestInit): Promise<T> => {
  const headers: Record<string, string> = {
    ...(init?.headers as Record<string, string> | undefined)
  };

  if (init?.body && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(input, {
    credentials: 'include',
    ...init,
    headers
  });

  const data = await parseJson<SuccessResponse>(response);

  if (!response.ok || (data && data.success === false)) {
    const message =
      (data && typeof data.error === 'string' && data.error) || response.statusText || 'Falha na requisição.';
    throw new Error(message);
  }

  return (data as T) ?? ({} as T);
};

export const fetchOfficers = async (): Promise<OfficerRecord[]> => {
  const data = await request<{ success: boolean; officers: OfficerRecord[] }>('/api/oficiais');
  return data.officers;
};

export const fetchPosts = async (): Promise<PostRecord[]> => {
  const data = await request<{ success: boolean; posts: PostRecord[] }>('/api/postos');
  return data.posts;
};

export const fetchDutyOfficers = async (): Promise<DutyAssignment | null> => {
  const data = await request<{ success: boolean; officers: DutyAssignment }>('/api/duty-officers');
  return data.officers ?? null;
};

type OfficerPayload = Pick<OfficerRecord, 'nome' | 'postoId' | 'status' | 'localizacao'>;

type UpdateOfficerPayload = OfficerPayload & { id: number };

export const createOfficer = async (payload: OfficerPayload): Promise<void> => {
  await request<{ success: boolean; message?: string }>('/api/oficiais', {
    method: 'POST',
    body: JSON.stringify(payload)
  });
};

export const updateOfficer = async (payload: UpdateOfficerPayload): Promise<void> => {
  await request<{ success: boolean; message?: string }>('/api/oficiais', {
    method: 'PUT',
    body: JSON.stringify(payload)
  });
};

export const deleteOfficer = async (id: number): Promise<void> => {
  await request<{ success: boolean; message?: string }>('/api/oficiais', {
    method: 'DELETE',
    body: JSON.stringify({ id })
  });
};

export const updateDutyAssignment = async (
  payload: Pick<DutyAssignment, 'officerName' | 'officerRank' | 'masterName' | 'masterRank'>
): Promise<void> => {
  await request<{ success: boolean; officers: DutyAssignment }>('/api/duty-officers', {
    method: 'PUT',
    body: JSON.stringify(payload)
  });
};
