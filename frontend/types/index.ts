// User Types
export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'manager' | 'user';
  created_at: string;
  updated_at: string;
}

// Task Types
export type TaskStatus = 'pending' | 'in_progress' | 'completed' | 'cancelled';
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

export interface Task {
  id: number;
  title: string;
  description: string | null;
  status: TaskStatus;
  priority: TaskPriority;
  assigned_user_id: number | null;
  assigned_user: User | null;
  created_by: number;
  creator: User | null;
  due_date: string | null;
  due_date_formatted?: string;
  created_at: string;
  updated_at: string;
  comments_count?: number;
  attachments_count?: number;
}

export interface TaskComment {
  id: number;
  task_id: number;
  user_id: number;
  user: User;
  content: string;
  created_at: string;
  updated_at: string;
}

export interface Attachment {
  id: number;
  task_id: number;
  file_name: string;
  file_path: string;
  file_size: number;
  mime_type?: string | null;
  file_type?: string | null;
  uploaded_by: number;
  uploader?: User;
  thumbnail_url?: string;
  download_url?: string;
  uploaded_at: string;
}

// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface LoginResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
}

// Form Types
export interface TaskFormData {
  title: string;
  description?: string;
  status?: TaskStatus;
  priority?: TaskPriority;
  assigned_user_id?: number | null;
  due_date?: string | null;
}

export interface CommentFormData {
  content: string;
}

export interface LoginFormData {
  email: string;
  password: string;
}

// Filter Types
export interface TaskFilters {
  status?: TaskStatus[];
  priority?: TaskPriority[];
  assigned_user_id?: number;
  search?: string;
  sort_by?: 'id' | 'title' | 'status' | 'priority' | 'due_date' | 'created_at' | 'updated_at';
  sort_order?: 'asc' | 'desc';
  page?: number;
  per_page?: number;
}

// UI State Types
export interface ToastMessage {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message?: string;
}

// Priority & Status Config
export const PRIORITY_CONFIG: Record<TaskPriority, { label: string; color: string; bgColor: string }> = {
  low: { label: 'Low', color: 'text-slate-500', bgColor: 'bg-slate-100' },
  medium: { label: 'Medium', color: 'text-amber-600', bgColor: 'bg-amber-50' },
  high: { label: 'High', color: 'text-orange-600', bgColor: 'bg-orange-50' },
  urgent: { label: 'Urgent', color: 'text-red-600', bgColor: 'bg-red-50' },
};

export const STATUS_CONFIG: Record<TaskStatus, { label: string; color: string; bgColor: string }> = {
  pending: { label: 'Pending', color: 'text-slate-600', bgColor: 'bg-slate-100' },
  in_progress: { label: 'In Progress', color: 'text-blue-600', bgColor: 'bg-blue-100' },
  completed: { label: 'Completed', color: 'text-emerald-600', bgColor: 'bg-emerald-100' },
  cancelled: { label: 'Cancelled', color: 'text-red-600', bgColor: 'bg-red-100' },
};
