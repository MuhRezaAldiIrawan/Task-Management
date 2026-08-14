'use client';

import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Task, TaskStatus, TaskPriority, User } from '@/types';
import { Button, Input, Textarea, Select } from '@/components/ui';
import { Modal } from '@/components/ui/Modal';
import { useToast } from '@/lib/toast';
import { api } from '@/lib/api';

const taskSchema = z.object({
  title: z.string().min(1, 'Title is required').max(255, 'Title is too long'),
  description: z.string().optional(),
  status: z.enum(['pending', 'in_progress', 'completed', 'cancelled']).optional(),
  priority: z.enum(['low', 'medium', 'high', 'urgent']).optional(),
  assigned_user_id: z.number().optional().nullable(),
  due_date: z.string().optional().nullable(),
});

type TaskFormData = z.infer<typeof taskSchema>;

interface TaskFormProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: (task: Task) => void;
  task?: Task;
  users?: User[];
}

export function TaskForm({ isOpen, onClose, onSuccess, task, users = [] }: TaskFormProps) {
  const { addToast } = useToast();
  const [isLoading, setIsLoading] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [isUploadingFile, setIsUploadingFile] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [availableUsers, setAvailableUsers] = useState<User[]>([]);
  const [isLoadingUsers, setIsLoadingUsers] = useState(false);

  const {
    register,
    handleSubmit,
    reset,
    setValue,
    watch,
    formState: { errors },
  } = useForm<TaskFormData>({
    resolver: zodResolver(taskSchema),
    defaultValues: {
      title: task?.title || '',
      description: task?.description || '',
      status: task?.status || 'pending',
      priority: task?.priority || 'medium',
      assigned_user_id: task?.assigned_user_id ?? null,
      due_date: task?.due_date || '',
    },
  });

  const selectedAssignedUserId = watch('assigned_user_id');

  useEffect(() => {
    if (task) {
      setValue('assigned_user_id', task.assigned_user_id ?? null, { shouldDirty: true });
    } else {
      setValue('assigned_user_id', null, { shouldDirty: true });
    }
  }, [task, setValue]);

  useEffect(() => {
    const fetchUsers = async () => {
      if (!isOpen) return;

      const hasToken = typeof window !== 'undefined' && !!localStorage.getItem('token');
      if (!hasToken) {
        setAvailableUsers([]);
        setValue('assigned_user_id', task?.assigned_user_id ?? null, { shouldDirty: true });
        setIsLoadingUsers(false);
        return;
      }

      setIsLoadingUsers(true);
      try {
        const response = await api.getUsers();
        const usersList = response.data ?? [];
        setAvailableUsers(usersList);

        if (task && task.assigned_user_id) {
          setValue('assigned_user_id', task.assigned_user_id, { shouldDirty: true });
        } else if (!task) {
          setValue('assigned_user_id', null, { shouldDirty: true });
        }
      } catch {
        setAvailableUsers([]);
        setValue('assigned_user_id', task?.assigned_user_id ?? null, { shouldDirty: true });
      } finally {
        setIsLoadingUsers(false);
      }
    };

    fetchUsers();
  }, [isOpen, task, setValue]);

  const onSubmit = async (data: TaskFormData) => {
    setIsLoading(true);

    try {
      const payload = {
        title: data.title,
        description: data.description || undefined,
        status: data.status,
        priority: data.priority,
        assigned_user_id: data.assigned_user_id || undefined,
        due_date: data.due_date || undefined,
      };

      let response;
      let createdTask: Task | null = null;

      if (task) {
        response = await api.updateTask(task.id, payload);
        createdTask = response.data;
      } else {
        response = await api.createTask(payload);
        createdTask = response.data;

        if (selectedFile && createdTask?.id) {
          setIsUploadingFile(true);
          setUploadProgress(0);

          try {
            await api.uploadAttachment(createdTask.id, selectedFile, setUploadProgress);
            addToast({
              type: 'success',
              title: 'Attachment uploaded',
              message: selectedFile.name,
            });
          } catch (uploadError) {
            addToast({
              type: 'error',
              title: 'Task created, attachment failed',
              message: uploadError instanceof Error ? uploadError.message : 'Failed to upload attachment',
            });
          } finally {
            setIsUploadingFile(false);
            setUploadProgress(0);
          }
        }
      }

      addToast({
        type: 'success',
        title: task ? 'Task updated' : 'Task created',
        message: response.message || 'Operation completed successfully',
      });

      reset();
      setSelectedFile(null);
      onSuccess(createdTask || response.data);
      onClose();
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to save task';
      addToast({
        type: 'error',
        title: 'Error',
        message,
      });
    } finally {
      setIsLoading(false);
    }
  };

  const statusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
  ];

  const priorityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent' },
  ];

  const userOptions = [{ value: '', label: 'Unassigned' }, ...availableUsers.map((u) => ({
    value: u.id,
    label: u.name,
  }))];

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={task ? 'Edit Task' : 'Create New Task'}
      size="lg"
    >
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        {/* Title */}
        <Input
          label="Title"
          placeholder="Enter task title"
          error={errors.title?.message}
          required
          {...register('title')}
        />

        {/* Description */}
        <Textarea
          label="Description"
          placeholder="Describe the task..."
          {...register('description')}
        />

        {/* Status & Priority Row */}
        <div className="grid grid-cols-2 gap-4">
          <Select
            label="Status"
            options={statusOptions}
            {...register('status')}
          />
          <Select
            label="Priority"
            options={priorityOptions}
            {...register('priority')}
          />
        </div>

        {/* Assigned User */}
        <Select
          label="Assign To"
          options={userOptions}
          value={selectedAssignedUserId ?? ''}
          disabled={isLoadingUsers}
          onChange={(event) => {
            const nextValue = event.target.value;
            setValue('assigned_user_id', nextValue === '' ? null : Number(nextValue), {
              shouldDirty: true,
              shouldTouch: true,
              shouldValidate: true,
            });
          }}
        />

        {/* Due Date */}
        <Input
          type="date"
          label="Due Date"
          {...register('due_date')}
        />

        {!task && (
          <div className="space-y-2">
            <label className="block text-sm font-medium text-slate-700">
              Attachment (optional)
            </label>
            <input
              type="file"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.png,.jpg,.jpeg,.gif,.webp,.mp4,.webm,.zip"
              onChange={(event) => setSelectedFile(event.target.files?.[0] || null)}
              className="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
            />
            {selectedFile && (
              <p className="text-xs text-slate-500">
                Selected: {selectedFile.name} ({(selectedFile.size / 1024 / 1024).toFixed(2)} MB)
              </p>
            )}
            {isUploadingFile && (
              <div className="space-y-1">
                <div className="w-full bg-slate-200 rounded-full h-2.5">
                  <div
                    className="bg-blue-600 h-2.5 rounded-full transition-all"
                    style={{ width: `${uploadProgress}%` }}
                  />
                </div>
                <p className="text-xs text-slate-500">Uploading attachment... {uploadProgress}%</p>
              </div>
            )}
          </div>
        )}

        {/* Actions */}
        <div className="flex justify-end gap-3 pt-4 border-t border-slate-200">
          <Button type="button" variant="secondary" onClick={() => {
            setSelectedFile(null);
            setUploadProgress(0);
            onClose();
          }}>
            Cancel
          </Button>
          <Button type="submit" isLoading={isLoading || isUploadingFile}>
            {task ? 'Save Changes' : 'Create Task'}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
