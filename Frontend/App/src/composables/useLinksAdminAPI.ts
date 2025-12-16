import { ref } from "vue";
import axios from "axios";
import type { AxiosError } from "axios";

export interface LinksSettings {
  l4r_enabled: string;
  l4r_linkvertise_enabled: string;
  l4r_linkvertise_user_id: string;
  l4r_linkvertise_coins_per_link: string;
  l4r_linkvertise_daily_limit: string;
  l4r_linkvertise_min_time_to_complete: string;
  l4r_linkvertise_time_to_expire: string;
  l4r_linkvertise_cooldown_time: string;
  l4r_shareus_enabled: string;
  l4r_shareus_api_key: string;
  l4r_shareus_coins_per_link: string;
  l4r_shareus_daily_limit: string;
  l4r_shareus_min_time_to_complete: string;
  l4r_shareus_time_to_expire: string;
  l4r_shareus_cooldown_time: string;
  l4r_linkpays_enabled: string;
  l4r_linkpays_api_key: string;
  l4r_linkpays_coins_per_link: string;
  l4r_linkpays_daily_limit: string;
  l4r_linkpays_min_time_to_complete: string;
  l4r_linkpays_time_to_expire: string;
  l4r_linkpays_cooldown_time: string;
  l4r_gyanilinks_enabled: string;
  l4r_gyanilinks_api_key: string;
  l4r_gyanilinks_coins_per_link: string;
  l4r_gyanilinks_daily_limit: string;
  l4r_gyanilinks_min_time_to_complete: string;
  l4r_gyanilinks_time_to_expire: string;
  l4r_gyanilinks_cooldown_time: string;
}

export function useLinksAdminAPI() {
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

  // Get settings
  const getSettings = async (): Promise<LinksSettings> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await axios.get<{
        success: boolean;
        data: LinksSettings;
        message?: string;
      }>("/api/admin/billinglinks/settings");
      
      if (response.data && response.data.success) {
        return response.data.data;
      }
      throw new Error("Failed to fetch settings");
    } catch (err) {
      const errorMsg = handleError(err);
      error.value = errorMsg;
      throw new Error(errorMsg);
    } finally {
      loading.value = false;
    }
  };

  // Update settings
  const updateSettings = async (
    settings: Partial<LinksSettings>
  ): Promise<LinksSettings> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await axios.patch<{
        success: boolean;
        data: LinksSettings;
        message?: string;
      }>("/api/admin/billinglinks/settings", settings);
      
      if (response.data && response.data.success) {
        return response.data.data;
      }
      throw new Error(response.data?.message || "Failed to update settings");
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
    getSettings,
    updateSettings,
  };
}


