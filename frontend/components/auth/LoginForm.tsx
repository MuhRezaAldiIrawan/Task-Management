'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useRouter } from 'next/navigation';
import { Lock, Mail } from 'lucide-react';
import { Button, Input } from '@/components/ui';
import { useToast } from '@/lib/toast';

const loginSchema = z.object({
  email: z.string().email('Please enter a valid email address'),
  password: z.string().min(6, 'Password must be at least 6 characters'),
});

type LoginFormData = z.infer<typeof loginSchema>;

export function LoginForm() {
  const router = useRouter();
  const { addToast } = useToast();
  const [isLoading, setIsLoading] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: '',
      password: '',
    },
  });

  const onSubmit = async (data: LoginFormData) => {
    setIsLoading(true);

    try {
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'}/auth/login`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(data),
        }
      );

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || 'Invalid credentials');
      }

      // Store the token
      localStorage.setItem('token', result.data.access_token);

      addToast({
        type: 'success',
        title: 'Welcome back!',
        message: 'You have successfully logged in.',
      });

      router.push('/dashboard');
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Login failed';

      addToast({
        type: 'error',
        title: 'Login Failed',
        message,
      });

      setError('email', { message: '' });
      setError('password', { message });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="w-full max-w-sm">
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
        {/* Email Field */}
        <Input
          label="Email"
          type="email"
          placeholder="admin@example.com"
          icon={<Mail className="h-4 w-4" />}
          error={errors.email?.message}
          disabled={isLoading}
          {...register('email')}
        />

        {/* Password Field */}
        <Input
          label="Password"
          type="password"
          placeholder="Enter your password"
          icon={<Lock className="h-4 w-4" />}
          error={errors.password?.message}
          disabled={isLoading}
          {...register('password')}
        />

        {/* Submit Button */}
        <Button
          type="submit"
          className="w-full"
          isLoading={isLoading}
          size="lg"
        >
          Sign In
        </Button>
      </form>

      {/* Demo Credentials Hint */}
      <div className="mt-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
        <p className="text-xs font-medium text-slate-600 mb-2">Demo Credentials:</p>
        <p className="text-xs text-slate-500">
          <span className="font-medium">Email:</span> admin@example.com
        </p>
        <p className="text-xs text-slate-500">
          <span className="font-medium">Password:</span> password
        </p>
      </div>
    </div>
  );
}
