const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

interface FetchOptions extends RequestInit {
  params?: Record<string, string | number | boolean | undefined>;
}

class ApiClient {
  private baseUrl: string;

  constructor(baseUrl: string) {
    this.baseUrl = baseUrl;
  }

  private getAuthToken(): string | null {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  }

  private clearAuthToken(): void {
    if (typeof window !== 'undefined') {
      localStorage.removeItem('token');
    }
  }

  private handleAuthFailure(response: Response, message: string): never {
    const normalizedMessage = message.toLowerCase();
    const isExpiredToken = response.status === 401 || response.status === 403 || /token|unauthenticated|unauthorized/.test(normalizedMessage);

    if (isExpiredToken) {
      this.clearAuthToken();

      if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/auth/')) {
        window.location.href = '/auth/login';
      }
    }

    throw new Error(message);
  }

  private buildUrl(endpoint: string, params?: Record<string, string | number | boolean | undefined>): string {
    const url = new URL(`${this.baseUrl}${endpoint}`, window.location.origin);

    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          if (Array.isArray(value)) {
            value.forEach(v => url.searchParams.append(key, String(v)));
          } else {
            url.searchParams.set(key, String(value));
          }
        }
      });
    }

    return url.toString();
  }

  async request<T>(endpoint: string, options: FetchOptions = {}): Promise<T> {
    const { params, ...fetchOptions } = options;
    const url = this.buildUrl(endpoint, params);

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    // Merge any custom headers
    if (options.headers) {
      Object.assign(headers, options.headers);
    }

    const token = this.getAuthToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    try {
      const response = await fetch(url, {
        ...fetchOptions,
        headers,
      });

      if (!response.ok) {
        const error = await response.json().catch(() => ({ message: 'An error occurred' }));
        const message = error.message || `HTTP error ${response.status}`;
        this.handleAuthFailure(response, message);
      }

      return response.json();
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to fetch';

      if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/auth/')) {
        this.clearAuthToken();
      }

      throw new Error(message);
    }
  }

  // Auth endpoints
  async login(email: string, password: string) {
    const data = await this.request<{ success: boolean; data: { access_token: string; token_type: string; expires_in: number } }>(
      '/auth/login',
      {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      }
    );

    if (data.data.access_token) {
      localStorage.setItem('token', data.data.access_token);
    }

    return data;
  }

  async logout() {
    localStorage.removeItem('token');
    return this.request('/auth/logout', { method: 'POST' });
  }

  async getCurrentUser() {
    return this.request<{ success: boolean; data: import('@/types').User }>('/auth/me');
  }

  async getUsers() {
    return this.request<{ success: boolean; data: import('@/types').User[] }>('/auth/users');
  }

  async getNotifications() {
    return {
      success: true,
      data: [],
    };
  }

  async markNotificationAsRead(_notificationId: string) {
    return {
      success: true,
      message: 'Notifications are temporarily disabled',
    };
  }

  // Task endpoints
  async getTasks(params?: Record<string, string | number | boolean | string[]>) {
    return this.request<{ success: boolean; data: import('@/types').Task[]; meta: import('@/types').PaginatedResponse<import('@/types').Task>['meta'] }>(
      '/auth/tasks',
      { params: params as Record<string, string | number | boolean | undefined> }
    );
  }

  async getTask(id: number) {
    return this.request<{ success: boolean; data: import('@/types').Task }>(`/auth/tasks/${id}`);
  }

  async createTask(data: import('@/types').TaskFormData) {
    return this.request<{ success: boolean; data: import('@/types').Task; message: string }>(
      '/auth/tasks',
      {
        method: 'POST',
        body: JSON.stringify(data),
      }
    );
  }

  async updateTask(id: number, data: Partial<import('@/types').TaskFormData>) {
    return this.request<{ success: boolean; data: import('@/types').Task; message: string }>(
      `/auth/tasks/${id}`,
      {
        method: 'PUT',
        body: JSON.stringify(data),
      }
    );
  }

  async deleteTask(id: number) {
    return this.request<{ success: boolean; message: string }>(
      `/auth/tasks/${id}`,
      { method: 'DELETE' }
    );
  }

  // Comment endpoints
  async getComments(taskId: number) {
    return this.request<{ success: boolean; data: import('@/types').TaskComment[] }>(
      `/auth/tasks/${taskId}/comments`
    );
  }

  async createComment(taskId: number, content: string) {
    return this.request<{ success: boolean; data: import('@/types').TaskComment; message: string }>(
      `/auth/tasks/${taskId}/comments`,
      {
        method: 'POST',
        body: JSON.stringify({ content }),
      }
    );
  }

  async updateComment(commentId: number, content: string) {
    return this.request<{ success: boolean; data: import('@/types').TaskComment; message: string }>(
      `/auth/comments/${commentId}`,
      {
        method: 'PUT',
        body: JSON.stringify({ content }),
      }
    );
  }

  async deleteComment(commentId: number) {
    return this.request<{ success: boolean; message: string }>(
      `/auth/comments/${commentId}`,
      { method: 'DELETE' }
    );
  }

  // Attachment endpoints
  async getAttachments(taskId: number) {
    return this.request<{ success: boolean; data: import('@/types').Attachment[] }>(
      `/auth/tasks/${taskId}/attachments`
    );
  }

  async uploadAttachment(taskId: number, file: File, onProgress?: (progress: number) => void) {
    const formData = new FormData();
    formData.append('file', file);

    const token = this.getAuthToken();

    return new Promise<{ success: boolean; data: import('@/types').Attachment }>((resolve, reject) => {
      const xhr = new XMLHttpRequest();

      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable && onProgress) {
          const progress = Math.round((e.loaded / e.total) * 100);
          onProgress(progress);
        }
      };

      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            const data = JSON.parse(xhr.responseText);
            resolve(data);
          } catch {
            reject(new Error('Invalid response'));
          }
        } else {
          try {
            const error = JSON.parse(xhr.responseText);
            reject(new Error(error.message || 'Upload failed'));
          } catch {
            reject(new Error('Upload failed'));
          }
        }
      };

      xhr.onerror = () => reject(new Error('Network error'));
      xhr.ontimeout = () => reject(new Error('Request timeout'));

      xhr.open('POST', `${this.baseUrl}/auth/tasks/${taskId}/attachments`);
      xhr.setRequestHeader('Accept', 'application/json');
      if (token) {
        xhr.setRequestHeader('Authorization', `Bearer ${token}`);
      }
      xhr.send(formData);
    });
  }

  async deleteAttachment(attachmentId: number) {
    return this.request<{ success: boolean; message: string }>(
      `/auth/attachments/${attachmentId}`,
      { method: 'DELETE' }
    );
  }

  getAttachmentUrl(filePath: string): string {
    // Extract the storage path and convert to full URL
    const cleanPath = filePath.replace(/^\/?storage\/?/, '');
    return `${this.baseUrl.replace('/api', '')}/storage/${cleanPath}`;
  }
}

export const api = new ApiClient(API_BASE_URL);

// Auth helper functions
export const auth = {
  isAuthenticated(): boolean {
    if (typeof window === 'undefined') return false;
    return !!localStorage.getItem('token');
  },

  getToken(): string | null {
    if (typeof window === 'undefined') return null;
    return localStorage.getItem('token');
  },

  setToken(token: string): void {
    localStorage.setItem('token', token);
  },

  removeToken(): void {
    localStorage.removeItem('token');
  },
};
