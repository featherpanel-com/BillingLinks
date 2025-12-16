import { ref } from "vue";
import axios from "axios";
import type { AxiosError } from "axios";

export interface LinkProvider {
  name: string;
  enabled: boolean;
  coins_per_link: number;
  daily_limit: number;
}

export interface Link {
  id: number;
  code: string;
  user_id: number;
  provider: string;
  completed: string;
  created_at: string;
  updated_at: string;
}

export interface ProvidersResponse {
  providers: LinkProvider[];
  l4r_enabled: boolean;
}

export interface HistoryResponse {
  links: Link[];
  total: number;
}

export interface StartLinkResponse {
  redirect_url: string;
  link_uuid: string;
  provider: string;
}

export function useLinksAPI() {
  const loading = ref(false);
  const error = ref<string | null>(null);

  const handleError = (err: unknown): string => {
    if (axios.isAxiosError(err)) {
      const axiosError = err as AxiosError<{
        message?: string;
        error_message?: string;
        error?: string;
      }>;
      return (
        axiosError.response?.data?.message ||
        axiosError.response?.data?.error_message ||
        axiosError.response?.data?.error ||
        axiosError.message ||
        "An error occurred"
      );
    }
    if (err instanceof Error) {
      return err.message;
    }
    return "An unknown error occurred";
  };

  // Get available providers
  const getProviders = async (): Promise<ProvidersResponse> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await axios.get<{
        success: boolean;
        data: ProvidersResponse;
        message?: string;
      }>("/api/user/billinglinks/providers");
      
      if (response.data && response.data.success) {
        return response.data.data;
      }
      throw new Error("Failed to fetch providers");
    } catch (err) {
      const errorMsg = handleError(err);
      error.value = errorMsg;
      throw new Error(errorMsg);
    } finally {
      loading.value = false;
    }
  };

  // Get link history
  const getHistory = async (): Promise<HistoryResponse> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await axios.get<{
        success: boolean;
        data: HistoryResponse;
        message?: string;
      }>("/api/user/billinglinks/history");
      
      if (response.data && response.data.success) {
        return response.data.data;
      }
      throw new Error("Failed to fetch history");
    } catch (err) {
      const errorMsg = handleError(err);
      error.value = errorMsg;
      throw new Error(errorMsg);
    } finally {
      loading.value = false;
    }
  };

  // Start a link
  const startLink = async (provider: string): Promise<StartLinkResponse> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await axios.get<{
        success: boolean;
        data: StartLinkResponse;
        message?: string;
      }>(`/api/user/billinglinks/start/${provider}`);
      
      if (response.data && response.data.success) {
        return response.data.data;
      }
      throw new Error(response.data?.message || "Failed to start link");
    } catch (err) {
      const errorMsg = handleError(err);
      error.value = errorMsg;
      throw new Error(errorMsg);
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    getProviders,
    getHistory,
    startLink,
  };
}


