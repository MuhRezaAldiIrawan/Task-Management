'use client';

import { useState, useEffect, useCallback } from 'react';
import { Task, TaskComment, Attachment, STATUS_CONFIG, PRIORITY_CONFIG } from '@/types';
import { SlideOver, Button, Textarea, Select, StatusBadge } from '@/components/ui';
import { TaskDetailSkeleton, CommentSkeleton } from '@/components/ui/Skeleton';
import { Avatar } from '@/components/ui/Avatar';
import { api } from '@/lib/api';
import { useToast } from '@/lib/toast';
import { formatRelativeTime, formatFileSize, isImageFile } from '@/lib/utils';
import {
  Calendar, User, FileText, Paperclip, MessageSquare,
  Trash2, Upload, Download, X, Image as ImageIcon
} from 'lucide-react';

interface TaskDetailProps {
  taskId: number | null;
  isOpen: boolean;
  onClose: () => void;
  onTaskUpdated: (task: Task) => void;
  onTaskDeleted: (taskId: number) => void;
}

export function TaskDetail({ taskId, isOpen, onClose, onTaskUpdated, onTaskDeleted }: TaskDetailProps) {
  const { addToast } = useToast();
  const [task, setTask] = useState<Task | null>(null);
  const [comments, setComments] = useState<TaskComment[]>([]);
  const [attachments, setAttachments] = useState<Attachment[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isCommentsLoading, setIsCommentsLoading] = useState(false);
  const [newComment, setNewComment] = useState('');
  const [isSubmittingComment, setIsSubmittingComment] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);

  const fetchTask = useCallback(async () => {
    if (!taskId) return;

    setIsLoading(true);
    try {
      const response = await api.getTask(taskId);
      setTask(response.data);
    } catch (error) {
      addToast({
        type: 'error',
        title: 'Error',
        message: 'Failed to load task details',
      });
    } finally {
      setIsLoading(false);
    }
  }, [taskId, addToast]);

  const fetchComments = useCallback(async () => {
    if (!taskId) return;

    setIsCommentsLoading(true);
    try {
      const response = await api.getComments(taskId);
      setComments(response.data);
    } catch {
      console.error('Failed to load comments');
    } finally {
      setIsCommentsLoading(false);
    }
  }, [taskId]);

  const fetchAttachments = useCallback(async () => {
    if (!taskId) return;

    try {
      const response = await api.getAttachments(taskId);
      setAttachments(response.data);
    } catch {
      console.error('Failed to load attachments');
    }
  }, [taskId]);

  useEffect(() => {
    if (isOpen && taskId) {
      fetchTask();
      fetchComments();
      fetchAttachments();
    }
  }, [isOpen, taskId, fetchTask, fetchComments, fetchAttachments]);

  const handleAddComment = async () => {
    if (!taskId || !newComment.trim()) return;

    setIsSubmittingComment(true);
    try {
      const response = await api.createComment(taskId, newComment.trim());
      setComments((prev) => [...prev, response.data]);
      setNewComment('');
      addToast({
        type: 'success',
        title: 'Comment added',
      });
    } catch (error) {
      addToast({
        type: 'error',
        title: 'Error',
        message: error instanceof Error ? error.message : 'Failed to add comment',
      });
    } finally {
      setIsSubmittingComment(false);
    }
  };

  const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file || !taskId) return;

    setIsUploading(true);
    setUploadProgress(0);

    try {
      const response = await api.uploadAttachment(taskId, file, setUploadProgress);
      setAttachments((prev) => [...prev, response.data]);
      addToast({
        type: 'success',
        title: 'File uploaded',
        message: file.name,
      });
    } catch (error) {
      addToast({
        type: 'error',
        title: 'Error',
        message: error instanceof Error ? error.message : 'Failed to upload file',
      });
    } finally {
      setIsUploading(false);
      setUploadProgress(0);
      e.target.value = '';
    }
  };

  const handleDeleteAttachment = async (attachmentId: number) => {
    try {
      await api.deleteAttachment(attachmentId);
      setAttachments((prev) => prev.filter((a) => a.id !== attachmentId));
      addToast({
        type: 'success',
        title: 'File deleted',
      });
    } catch (error) {
      addToast({
        type: 'error',
        title: 'Error',
        message: error instanceof Error ? error.message : 'Failed to delete file',
      });
    }
  };

  const handleStatusChange = async (status: string) => {
    if (!task) return;

    try {
      const response = await api.updateTask(task.id, { status: status as Task['status'] });
      setTask(response.data);
      onTaskUpdated(response.data);
      addToast({
        type: 'success',
        title: 'Status updated',
      });
    } catch (error) {
      addToast({
        type: 'error',
        title: 'Error',
        message: error instanceof Error ? error.message : 'Failed to update status',
      });
    }
  };

  const handleDeleteTask = async () => {
    if (!task || !confirm('Are you sure you want to delete this task?')) return;

    try {
      await api.deleteTask(task.id);
      addToast({
        type: 'success',
        title: 'Task deleted',
      });
      onTaskDeleted(task.id);
      onClose();
    } catch (error) {
      addToast({
        type: 'error',
        title: 'Error',
        message: error instanceof Error ? error.message : 'Failed to delete task',
      });
    }
  };

  const statusOptions = Object.entries(STATUS_CONFIG).map(([value, config]) => ({
    value,
    label: config.label,
  }));

  return (
    <SlideOver isOpen={isOpen} onClose={onClose} title="Task Details" width="lg">
      {isLoading ? (
        <TaskDetailSkeleton />
      ) : task ? (
        <div className="space-y-6">
          {/* Header */}
          <div>
            <h2 className="text-xl font-bold text-[var(--color-text-primary)] mb-3">
              {task.title}
            </h2>
            <div className="flex items-center gap-3">
              <select
                value={task.status}
                onChange={(e) => handleStatusChange(e.target.value)}
                className="text-sm font-medium rounded-full px-3 py-1 bg-slate-100 hover:bg-slate-200 cursor-pointer"
              >
                {statusOptions.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
              <div className={`flex items-center gap-1 text-sm ${PRIORITY_CONFIG[task.priority].color}`}>
                <span className="font-medium">{PRIORITY_CONFIG[task.priority].label}</span>
                <span>Priority</span>
              </div>
            </div>
          </div>

          {/* Description */}
          <div>
            <h3 className="text-sm font-semibold text-[var(--color-text-primary)] mb-2">Description</h3>
            <p className="text-sm text-[var(--color-text-secondary)] whitespace-pre-wrap">
              {task.description || 'No description provided.'}
            </p>
          </div>

          {/* Meta Info */}
          <div className="grid grid-cols-2 gap-4">
            <div className="flex items-center gap-2 text-sm">
              <Calendar className="h-4 w-4 text-[var(--color-text-muted)]" />
              <span className="text-[var(--color-text-secondary)]">
                {task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date'}
              </span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <User className="h-4 w-4 text-[var(--color-text-muted)]" />
              <span className="text-[var(--color-text-secondary)]">
                {task.assigned_user?.name || 'Unassigned'}
              </span>
            </div>
          </div>

          {/* Attachments */}
          <div>
            <div className="flex items-center justify-between mb-3">
              <h3 className="text-sm font-semibold text-[var(--color-text-primary)] flex items-center gap-2">
                <Paperclip className="h-4 w-4" />
                Attachments ({attachments.length})
              </h3>
              <label className="cursor-pointer">
                <input
                  type="file"
                  className="hidden"
                  onChange={handleFileUpload}
                  disabled={isUploading}
                />
                <span className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-slate-100 hover:bg-slate-200 rounded-md transition-colors">
                  <Upload className="h-3.5 w-3.5" />
                  Upload
                </span>
              </label>
            </div>

            {isUploading && (
              <div className="mb-3">
                <div className="h-2 bg-slate-200 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-[var(--color-accent)] transition-all duration-300"
                    style={{ width: `${uploadProgress}%` }}
                  />
                </div>
              </div>
            )}

            {attachments.length > 0 ? (
              <div className="flex flex-wrap gap-2">
                {attachments.map((attachment) => (
                  <div
                    key={attachment.id}
                    className="flex items-center gap-2 p-2 bg-slate-50 rounded-md border border-slate-200 group"
                  >
                    {isImageFile(attachment.mime_type) ? (
                      <ImageIcon className="h-5 w-5 text-blue-500" />
                    ) : (
                      <FileText className="h-5 w-5 text-slate-500" />
                    )}
                    <div className="min-w-0">
                      <p className="text-xs font-medium text-[var(--color-text-primary)] truncate max-w-[120px]">
                        {attachment.file_name}
                      </p>
                      <p className="text-xs text-[var(--color-text-muted)]">
                        {formatFileSize(attachment.file_size)}
                      </p>
                    </div>
                    <button
                      onClick={() => handleDeleteAttachment(attachment.id)}
                      className="opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-red-500 transition-opacity"
                    >
                      <X className="h-3.5 w-3.5" />
                    </button>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-[var(--color-text-muted)]">No attachments yet.</p>
            )}
          </div>

          {/* Comments */}
          <div>
            <h3 className="text-sm font-semibold text-[var(--color-text-primary)] flex items-center gap-2 mb-3">
              <MessageSquare className="h-4 w-4" />
              Comments ({comments.length})
            </h3>

            {isCommentsLoading ? (
              <div className="space-y-4">
                <CommentSkeleton />
                <CommentSkeleton />
              </div>
            ) : (
              <div className="space-y-4 mb-4">
                {comments.map((comment) => (
                  <div key={comment.id} className="flex gap-3">
                    <Avatar name={comment.user?.name || 'User'} size="sm" />
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <span className="text-sm font-medium text-[var(--color-text-primary)]">
                          {comment.user?.name || 'User'}
                        </span>
                        <span className="text-xs text-[var(--color-text-muted)]">
                          {formatRelativeTime(comment.created_at)}
                        </span>
                      </div>
                      <p className="text-sm text-[var(--color-text-secondary)] mt-1">
                        {comment.content}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* Add Comment */}
            <div className="flex gap-2">
              <textarea
                value={newComment}
                onChange={(e) => setNewComment(e.target.value)}
                placeholder="Add a comment..."
                className="flex-1 min-h-[80px] p-3 text-sm border border-[var(--color-border)] rounded-md resize-y focus:border-[var(--color-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]/20"
              />
              <Button
                onClick={handleAddComment}
                isLoading={isSubmittingComment}
                disabled={!newComment.trim()}
                className="self-end"
              >
                Send
              </Button>
            </div>
          </div>

          {/* Actions */}
          <div className="flex justify-between pt-4 border-t border-slate-200">
            <Button variant="danger" onClick={handleDeleteTask}>
              <Trash2 className="h-4 w-4 mr-1.5" />
              Delete Task
            </Button>
          </div>
        </div>
      ) : (
        <p className="text-[var(--color-text-secondary)]">Task not found.</p>
      )}
    </SlideOver>
  );
}
