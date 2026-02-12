<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { Card } from "@/components/ui/card";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Loader2, Settings, Save } from "lucide-vue-next";
import {
  useLinksAdminAPI,
  type LinksSettings,
} from "@/composables/useLinksAdminAPI";
import { useToast } from "vue-toastification";

const toast = useToast();
const { getSettings, updateSettings, loading } = useLinksAdminAPI();

// Settings
const settings = ref<LinksSettings | null>(null);
const savingSettings = ref(false);

// Active tab
const activeTab = ref("settings");

const loadSettings = async () => {
  try {
    settings.value = await getSettings();
  } catch (err) {
    toast.error(err instanceof Error ? err.message : "Failed to load settings");
  }
};

const saveSettings = async () => {
  if (!settings.value) return;

  savingSettings.value = true;
  try {
    settings.value = await updateSettings(settings.value);
    toast.success("Settings saved successfully!");
  } catch (err) {
    toast.error(err instanceof Error ? err.message : "Failed to save settings");
  } finally {
    savingSettings.value = false;
  }
};

const updateSetting = (key: keyof LinksSettings, value: string) => {
  if (!settings.value) return;
  (settings.value as Record<string, string>)[key] = value;
};

const isEnabled = computed(() => {
  return settings.value?.l4r_enabled === "true";
});

const isProviderEnabled = (provider: string) => {
  if (!settings.value) return false;
  const key = `l4r_${provider}_enabled` as keyof LinksSettings;
  return settings.value[key] === "true";
};

const getProviderValue = (provider: string, field: string): string => {
  if (!settings.value) return "";
  const key = `l4r_${provider}_${field}` as keyof LinksSettings;
  return settings.value[key] || "";
};

const setProviderValue = (
  provider: string,
  field: string,
  value: string | number
) => {
  if (!settings.value) return;
  const key = `l4r_${provider}_${field}` as keyof LinksSettings;
  (settings.value as Record<string, string>)[key] = String(value);
};

onMounted(() => {
  loadSettings();
});
</script>

<template>
  <div class="w-full h-full overflow-auto p-4 md:p-8 min-h-screen">
    <div class="container mx-auto max-w-6xl">
      <div class="mb-6 text-center md:text-left">
        <h1
          class="text-3xl font-bold bg-gradient-to-r from-primary to-primary/60 bg-clip-text text-transparent"
        >
          Links4Rewards - Admin
        </h1>
        <p class="text-muted-foreground mt-2">
          Configure Links4Rewards settings and manage link providers
        </p>
      </div>

      <Tabs v-model="activeTab" class="w-full">
        <TabsList class="grid w-full grid-cols-1 bg-muted/30 border border-border/50">
          <TabsTrigger value="settings">
            <Settings class="h-4 w-4 mr-2" />
            Settings
          </TabsTrigger>
        </TabsList>

        <TabsContent value="settings" class="mt-4">
          <Card class="border-2 shadow-xl bg-card/50 backdrop-blur-sm">
            <div class="p-6">
              <div
                v-if="loading && !settings"
                class="flex items-center justify-center py-12"
              >
                <Loader2 class="h-8 w-8 animate-spin" />
              </div>
              <form
                v-else-if="settings"
                @submit.prevent="saveSettings"
                class="space-y-6"
              >
                <!-- Enable/Disable -->
                <div
                  class="flex items-center justify-between p-4 rounded-lg bg-muted/30 border border-border/50"
                >
                  <div>
                    <Label class="text-base font-semibold"
                      >Enable Links4Rewards</Label
                    >
                    <p class="text-sm text-muted-foreground">
                      Allow users to earn credits by completing links
                    </p>
                  </div>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="isEnabled"
                    @click="
                      updateSetting('l4r_enabled', isEnabled ? 'false' : 'true')
                    "
                    :class="[
                      'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background',
                      isEnabled ? 'bg-primary' : 'bg-muted',
                    ]"
                  >
                    <span
                      class="pointer-events-none block h-5 w-5 rounded-full bg-white shadow-lg ring-0 transition-transform"
                      :class="isEnabled ? 'translate-x-5' : 'translate-x-0.5'"
                    />
                  </button>
                </div>

                <!-- Provider Settings -->
                <div v-if="isEnabled" class="space-y-6">
                  <h3 class="text-lg font-semibold">Link Providers</h3>
                  <p class="text-sm text-muted-foreground">
                    Configure settings for each link provider
                  </p>

                  <!-- Linkvertise -->
                  <div
                    class="border border-border/50 rounded-lg p-4 space-y-4 bg-muted/20"
                  >
                    <div class="flex justify-between items-start">
                      <div>
                        <h4 class="font-medium text-lg">Linkvertise</h4>
                        <p class="text-sm text-muted-foreground">
                          Enable earning from Linkvertise shortlinks
                        </p>
                      </div>
                      <button
                        type="button"
                        role="switch"
                        :aria-checked="isProviderEnabled('linkvertise')"
                        @click="
                          updateSetting(
                            'l4r_linkvertise_enabled',
                            isProviderEnabled('linkvertise') ? 'false' : 'true'
                          )
                        "
                        :class="[
                          'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background',
                          isProviderEnabled('linkvertise') ? 'bg-primary' : 'bg-muted',
                        ]"
                      >
                        <span
                          class="pointer-events-none block h-5 w-5 rounded-full bg-white shadow-lg ring-0 transition-transform"
                          :class="
                            isProviderEnabled('linkvertise')
                              ? 'translate-x-5'
                              : 'translate-x-0.5'
                          "
                        />
                      </button>
                    </div>

                    <div
                      v-if="isProviderEnabled('linkvertise')"
                      class="space-y-4 pt-4 border-t"
                    >
                      <div>
                        <Label for="linkvertise_user_id">User ID</Label>
                        <Input
                          id="linkvertise_user_id"
                          :model-value="
                            getProviderValue('linkvertise', 'user_id')
                          "
                          @update:model-value="
                            setProviderValue('linkvertise', 'user_id', $event)
                          "
                          type="text"
                          placeholder="Enter Linkvertise User ID"
                          class="mt-2"
                        />
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <Label for="linkvertise_coins_per_link">
                            Coins Per Link
                          </Label>
                          <Input
                            id="linkvertise_coins_per_link"
                            :model-value="
                              getProviderValue('linkvertise', 'coins_per_link')
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkvertise',
                                'coins_per_link',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="linkvertise_daily_limit">
                            Daily Limit
                          </Label>
                          <Input
                            id="linkvertise_daily_limit"
                            :model-value="
                              getProviderValue('linkvertise', 'daily_limit')
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkvertise',
                                'daily_limit',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                          <Label for="linkvertise_min_time_to_complete">
                            Min Time to Complete (seconds)
                          </Label>
                          <Input
                            id="linkvertise_min_time_to_complete"
                            :model-value="
                              getProviderValue(
                                'linkvertise',
                                'min_time_to_complete'
                              )
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkvertise',
                                'min_time_to_complete',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="linkvertise_time_to_expire">
                            Time to Expire (seconds)
                          </Label>
                          <Input
                            id="linkvertise_time_to_expire"
                            :model-value="
                              getProviderValue('linkvertise', 'time_to_expire')
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkvertise',
                                'time_to_expire',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="linkvertise_cooldown_time">
                            Cooldown Time (seconds)
                          </Label>
                          <Input
                            id="linkvertise_cooldown_time"
                            :model-value="
                              getProviderValue('linkvertise', 'cooldown_time')
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkvertise',
                                'cooldown_time',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- ShareUs -->
                  <div
                    class="border border-border/50 rounded-lg p-4 space-y-4 bg-muted/20"
                  >
                    <div class="flex justify-between items-start">
                      <div>
                        <h4 class="font-medium text-lg">ShareUs</h4>
                        <p class="text-sm text-muted-foreground">
                          Enable earning from ShareUs shortlinks
                        </p>
                      </div>
                      <button
                        type="button"
                        role="switch"
                        :aria-checked="isProviderEnabled('shareus')"
                        @click="
                          updateSetting(
                            'l4r_shareus_enabled',
                            isProviderEnabled('shareus') ? 'false' : 'true'
                          )
                        "
                        :class="[
                          'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background',
                          isProviderEnabled('shareus') ? 'bg-primary' : 'bg-muted',
                        ]"
                      >
                        <span
                          class="pointer-events-none block h-5 w-5 rounded-full bg-white shadow-lg ring-0 transition-transform"
                          :class="
                            isProviderEnabled('shareus')
                              ? 'translate-x-5'
                              : 'translate-x-0.5'
                          "
                        />
                      </button>
                    </div>

                    <div
                      v-if="isProviderEnabled('shareus')"
                      class="space-y-4 pt-4 border-t"
                    >
                      <div>
                        <Label for="shareus_api_key">API Key</Label>
                        <Input
                          id="shareus_api_key"
                          :model-value="getProviderValue('shareus', 'api_key')"
                          @update:model-value="
                            setProviderValue('shareus', 'api_key', $event)
                          "
                          type="password"
                          placeholder="Enter ShareUs API Key"
                          class="mt-2"
                        />
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <Label for="shareus_coins_per_link">
                            Coins Per Link
                          </Label>
                          <Input
                            id="shareus_coins_per_link"
                            :model-value="
                              getProviderValue('shareus', 'coins_per_link')
                            "
                            @update:model-value="
                              setProviderValue(
                                'shareus',
                                'coins_per_link',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="shareus_daily_limit">Daily Limit</Label>
                          <Input
                            id="shareus_daily_limit"
                            :model-value="
                              getProviderValue('shareus', 'daily_limit')
                            "
                            @update:model-value="
                              setProviderValue('shareus', 'daily_limit', $event)
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                          <Label for="shareus_min_time_to_complete">
                            Min Time to Complete (seconds)
                          </Label>
                          <Input
                            id="shareus_min_time_to_complete"
                            :model-value="
                              getProviderValue(
                                'shareus',
                                'min_time_to_complete'
                              )
                            "
                            @update:model-value="
                              setProviderValue(
                                'shareus',
                                'min_time_to_complete',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="shareus_time_to_expire">
                            Time to Expire (seconds)
                          </Label>
                          <Input
                            id="shareus_time_to_expire"
                            :model-value="
                              getProviderValue('shareus', 'time_to_expire')
                            "
                            @update:model-value="
                              setProviderValue(
                                'shareus',
                                'time_to_expire',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="shareus_cooldown_time">
                            Cooldown Time (seconds)
                          </Label>
                          <Input
                            id="shareus_cooldown_time"
                            :model-value="
                              getProviderValue('shareus', 'cooldown_time')
                            "
                            @update:model-value="
                              setProviderValue(
                                'shareus',
                                'cooldown_time',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- LinkPays -->
                  <div
                    class="border border-border/50 rounded-lg p-4 space-y-4 bg-muted/20"
                  >
                    <div class="flex justify-between items-start">
                      <div>
                        <h4 class="font-medium text-lg">LinkPays</h4>
                        <p class="text-sm text-muted-foreground">
                          Enable earning from LinkPays shortlinks
                        </p>
                      </div>
                      <button
                        type="button"
                        role="switch"
                        :aria-checked="isProviderEnabled('linkpays')"
                        @click="
                          updateSetting(
                            'l4r_linkpays_enabled',
                            isProviderEnabled('linkpays') ? 'false' : 'true'
                          )
                        "
                        :class="[
                          'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background',
                          isProviderEnabled('linkpays') ? 'bg-primary' : 'bg-muted',
                        ]"
                      >
                        <span
                          class="pointer-events-none block h-5 w-5 rounded-full bg-white shadow-lg ring-0 transition-transform"
                          :class="
                            isProviderEnabled('linkpays')
                              ? 'translate-x-5'
                              : 'translate-x-0.5'
                          "
                        />
                      </button>
                    </div>

                    <div
                      v-if="isProviderEnabled('linkpays')"
                      class="space-y-4 pt-4 border-t"
                    >
                      <div>
                        <Label for="linkpays_api_key">API Key</Label>
                        <Input
                          id="linkpays_api_key"
                          :model-value="getProviderValue('linkpays', 'api_key')"
                          @update:model-value="
                            setProviderValue('linkpays', 'api_key', $event)
                          "
                          type="password"
                          placeholder="Enter LinkPays API Key"
                          class="mt-2"
                        />
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <Label for="linkpays_coins_per_link">
                            Coins Per Link
                          </Label>
                          <Input
                            id="linkpays_coins_per_link"
                            :model-value="
                              getProviderValue('linkpays', 'coins_per_link')
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkpays',
                                'coins_per_link',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="linkpays_daily_limit">Daily Limit</Label>
                          <Input
                            id="linkpays_daily_limit"
                            :model-value="
                              getProviderValue('linkpays', 'daily_limit')
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkpays',
                                'daily_limit',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                          <Label for="linkpays_min_time_to_complete">
                            Min Time to Complete (seconds)
                          </Label>
                          <Input
                            id="linkpays_min_time_to_complete"
                            :model-value="
                              getProviderValue(
                                'linkpays',
                                'min_time_to_complete'
                              )
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkpays',
                                'min_time_to_complete',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="linkpays_time_to_expire">
                            Time to Expire (seconds)
                          </Label>
                          <Input
                            id="linkpays_time_to_expire"
                            :model-value="
                              getProviderValue('linkpays', 'time_to_expire')
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkpays',
                                'time_to_expire',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="linkpays_cooldown_time">
                            Cooldown Time (seconds)
                          </Label>
                          <Input
                            id="linkpays_cooldown_time"
                            :model-value="
                              getProviderValue('linkpays', 'cooldown_time')
                            "
                            @update:model-value="
                              setProviderValue(
                                'linkpays',
                                'cooldown_time',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- GyaniLinks -->
                  <div
                    class="border border-border/50 rounded-lg p-4 space-y-4 bg-muted/20"
                  >
                    <div class="flex justify-between items-start">
                      <div>
                        <h4 class="font-medium text-lg">GyaniLinks</h4>
                        <p class="text-sm text-muted-foreground">
                          Enable earning from GyaniLinks shortlinks
                        </p>
                      </div>
                      <button
                        type="button"
                        role="switch"
                        :aria-checked="isProviderEnabled('gyanilinks')"
                        @click="
                          updateSetting(
                            'l4r_gyanilinks_enabled',
                            isProviderEnabled('gyanilinks') ? 'false' : 'true'
                          )
                        "
                        :class="[
                          'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background',
                          isProviderEnabled('gyanilinks') ? 'bg-primary' : 'bg-muted',
                        ]"
                      >
                        <span
                          class="pointer-events-none block h-5 w-5 rounded-full bg-white shadow-lg ring-0 transition-transform"
                          :class="
                            isProviderEnabled('gyanilinks')
                              ? 'translate-x-5'
                              : 'translate-x-0.5'
                          "
                        />
                      </button>
                    </div>

                    <div
                      v-if="isProviderEnabled('gyanilinks')"
                      class="space-y-4 pt-4 border-t"
                    >
                      <div>
                        <Label for="gyanilinks_api_key">API Key</Label>
                        <Input
                          id="gyanilinks_api_key"
                          :model-value="
                            getProviderValue('gyanilinks', 'api_key')
                          "
                          @update:model-value="
                            setProviderValue('gyanilinks', 'api_key', $event)
                          "
                          type="password"
                          placeholder="Enter GyaniLinks API Key"
                          class="mt-2"
                        />
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <Label for="gyanilinks_coins_per_link">
                            Coins Per Link
                          </Label>
                          <Input
                            id="gyanilinks_coins_per_link"
                            :model-value="
                              getProviderValue('gyanilinks', 'coins_per_link')
                            "
                            @update:model-value="
                              setProviderValue(
                                'gyanilinks',
                                'coins_per_link',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="gyanilinks_daily_limit">
                            Daily Limit
                          </Label>
                          <Input
                            id="gyanilinks_daily_limit"
                            :model-value="
                              getProviderValue('gyanilinks', 'daily_limit')
                            "
                            @update:model-value="
                              setProviderValue(
                                'gyanilinks',
                                'daily_limit',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                          <Label for="gyanilinks_min_time_to_complete">
                            Min Time to Complete (seconds)
                          </Label>
                          <Input
                            id="gyanilinks_min_time_to_complete"
                            :model-value="
                              getProviderValue(
                                'gyanilinks',
                                'min_time_to_complete'
                              )
                            "
                            @update:model-value="
                              setProviderValue(
                                'gyanilinks',
                                'min_time_to_complete',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="gyanilinks_time_to_expire">
                            Time to Expire (seconds)
                          </Label>
                          <Input
                            id="gyanilinks_time_to_expire"
                            :model-value="
                              getProviderValue('gyanilinks', 'time_to_expire')
                            "
                            @update:model-value="
                              setProviderValue(
                                'gyanilinks',
                                'time_to_expire',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>

                        <div>
                          <Label for="gyanilinks_cooldown_time">
                            Cooldown Time (seconds)
                          </Label>
                          <Input
                            id="gyanilinks_cooldown_time"
                            :model-value="
                              getProviderValue('gyanilinks', 'cooldown_time')
                            "
                            @update:model-value="
                              setProviderValue(
                                'gyanilinks',
                                'cooldown_time',
                                $event
                              )
                            "
                            type="number"
                            min="1"
                            class="mt-2"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex justify-end pt-4 border-t">
                  <Button type="submit" :disabled="savingSettings">
                    <Loader2
                      v-if="savingSettings"
                      class="h-4 w-4 mr-2 animate-spin"
                    />
                    <Save v-else class="h-4 w-4 mr-2" />
                    Save Settings
                  </Button>
                </div>
              </form>
            </div>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </div>
</template>
