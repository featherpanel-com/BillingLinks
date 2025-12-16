<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Wallet,
  Loader2,
  Link as LinkIcon,
  Coins,
  ExternalLink,
  TrendingUp,
} from "lucide-vue-next";
import { useLinksAPI, type LinkProvider } from "@/composables/useLinksAPI";
import { useToast } from "vue-toastification";
import axios from "axios";

const toast = useToast();
const { loading, getProviders } = useLinksAPI();

const providers = ref<LinkProvider[]>([]);
const totalCoins = ref(0);
const l4rEnabled = ref(true);

// Get provider logo
const getProviderLogo = (provider: string): string => {
  const logoMap: Record<string, string> = {
    linkvertise: "https://cdn.mythical.systems/mythicaldash/linkvertise.png",
    shareus: "https://cdn.mythical.systems/mythicaldash/shareus.png",
    linkpays: "https://cdn.mythical.systems/mythicaldash/linkpays.jpg",
    gyanilinks: "http://cdn.mythical.systems/mythicaldash/gyanilinks.png",
  };

  return logoMap[provider.toLowerCase()] || "https://via.placeholder.com/48";
};

// Get provider name
const getProviderName = (provider: string): string => {
  const nameMap: Record<string, string> = {
    linkvertise: "Linkvertise",
    shareus: "ShareUs",
    linkpays: "LinkPays",
    gyanilinks: "GyaniLinks",
  };

  return nameMap[provider.toLowerCase()] || provider;
};

// Get provider tag
const getProviderTag = (provider: string): string => {
  const tagMap: Record<string, string> = {
    linkvertise: "Popular",
    shareus: "Fast",
    linkpays: "Reliable",
    gyanilinks: "New",
  };

  return tagMap[provider.toLowerCase()] || "Available";
};

// Load providers and credits
const loadData = async () => {
  try {
    const providersData = await getProviders();
    providers.value = providersData.providers;
    l4rEnabled.value = providersData.l4r_enabled;

    // Load credits
    await loadCredits();
  } catch (err) {
    toast.error(
      err instanceof Error ? err.message : "Failed to load providers"
    );
  }
};

// Go to link provider - open in new tab
const goToLinkProvider = (providerKey: string) => {
  // Build the URL to the start endpoint
  const startUrl = `/api/user/billinglinks/start/${providerKey}`;

  // Open in a new tab
  const newWindow = window.open(startUrl, "_blank");

  if (newWindow) {
    toast.success(
      `Opening ${getProviderName(providerKey)} link in a new tab...`
    );

    // Set up a listener to reload credits when the window might close
    // We'll also reload credits periodically
    const checkInterval = setInterval(() => {
      if (newWindow.closed) {
        clearInterval(checkInterval);
        loadCredits();
      }
    }, 1000);

    // Also reload credits after a delay in case the link was completed
    setTimeout(() => {
      loadCredits();
    }, 5000);
  } else {
    toast.error("Please allow popups to open the link");
  }
};

// Load credits
const loadCredits = async () => {
  try {
    const creditsResponse = await axios.get("/api/user/billingcore/credits");
    if (creditsResponse.data?.success) {
      totalCoins.value = creditsResponse.data.data?.credits || 0;
    }
  } catch (err) {
    console.error("Failed to load credits:", err);
  }
};

onMounted(() => {
  loadData();
});
</script>

<template>
  <div
    class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 p-4 md:p-8"
  >
    <div class="max-w-6xl mx-auto space-y-8">
      <!-- Header Section -->
      <div class="text-center space-y-4">
        <div class="flex items-center justify-center gap-3">
          <div class="relative">
            <div
              class="absolute inset-0 bg-primary/20 blur-2xl rounded-full"
            ></div>
            <LinkIcon class="relative h-12 w-12 text-primary" />
          </div>
        </div>
        <div>
          <h1
            class="text-5xl font-bold bg-gradient-to-r from-primary to-primary/60 bg-clip-text text-transparent"
          >
            Links for Rewards
          </h1>
          <p class="text-lg text-muted-foreground mt-2">
            Complete links from various providers to earn credits!
          </p>
        </div>
      </div>

      <!-- User Credits Card -->
      <Card class="p-8 md:p-10 border-2 shadow-xl bg-card/50 backdrop-blur-sm">
        <div class="space-y-4">
          <div class="flex items-center gap-3 mb-4">
            <div class="p-2 rounded-lg bg-primary/10">
              <Wallet class="h-6 w-6 text-primary" />
            </div>
            <div>
              <h2 class="text-2xl font-bold">Your Credits</h2>
              <p class="text-sm text-muted-foreground">
                Current credit balance
              </p>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <div class="text-4xl font-bold">{{ totalCoins }}</div>
            <Badge variant="secondary" class="text-lg px-3 py-1">
              Credits
            </Badge>
          </div>
        </div>
      </Card>

      <!-- Main L4R Content -->
      <Card class="p-8 md:p-10 border-2 shadow-xl bg-card/50 backdrop-blur-sm">
        <div class="space-y-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 rounded-lg bg-primary/10">
              <TrendingUp class="h-6 w-6 text-primary" />
            </div>
            <div>
              <h2 class="text-2xl font-bold">Available Providers</h2>
              <p class="text-sm text-muted-foreground">
                Click on a provider to start earning credits
              </p>
            </div>
          </div>

          <div
            v-if="loading && providers.length === 0"
            class="flex items-center justify-center py-12"
          >
            <Loader2 class="h-8 w-8 animate-spin text-primary" />
          </div>

          <div v-else-if="!l4rEnabled" class="text-center py-12">
            <p class="text-muted-foreground">
              Links4Rewards is currently disabled.
            </p>
          </div>

          <div v-else-if="providers.length === 0" class="text-center py-12">
            <p class="text-muted-foreground">
              No link providers are currently available.
            </p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Provider Cards -->
            <Card
              v-for="provider in providers"
              :key="provider.name"
              class="border-2 shadow-lg bg-card/50 backdrop-blur-sm hover:shadow-xl transition-all hover:border-primary/50"
            >
              <div class="p-6">
                <div class="flex items-center mb-4">
                  <div class="p-2 rounded-lg bg-primary/10 mr-3">
                    <img
                      :src="getProviderLogo(provider.name)"
                      :alt="getProviderName(provider.name)"
                      class="h-10 w-10 rounded-lg object-cover"
                    />
                  </div>
                  <div class="flex-1">
                    <h3 class="text-lg font-bold">
                      {{ getProviderName(provider.name) }}
                    </h3>
                    <Badge variant="secondary" class="text-xs mt-1">
                      {{ getProviderTag(provider.name) }}
                    </Badge>
                  </div>
                </div>

                <p class="text-muted-foreground text-sm mb-4">
                  Complete links to earn credits with
                  {{ getProviderName(provider.name) }}.
                </p>

                <div class="flex items-center justify-between mb-3">
                  <div
                    class="flex items-center bg-primary/10 text-primary px-3 py-1.5 rounded-full text-xs font-medium"
                  >
                    <Coins class="h-3 w-3 mr-1" />
                    {{ provider.coins_per_link }} credits per link
                  </div>

                  <Button
                    @click="goToLinkProvider(provider.name)"
                    :disabled="loading"
                    class="px-4 py-2"
                  >
                    <ExternalLink class="h-4 w-4 mr-2" />
                    Start Link
                  </Button>
                </div>

                <div
                  class="text-xs text-muted-foreground bg-muted/30 p-2 rounded-lg"
                >
                  Daily limit: {{ provider.daily_limit }} links
                </div>
              </div>
            </Card>
          </div>
        </div>
      </Card>

      <!-- How It Works Card -->
      <Card class="p-8 md:p-10 border-2 shadow-xl bg-card/50 backdrop-blur-sm">
        <div class="space-y-6">
          <h2 class="text-2xl font-bold">How It Works</h2>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-muted/30 p-4 rounded-lg border border-border/50">
              <div class="flex items-start mb-2">
                <div
                  class="bg-primary/20 text-primary w-6 h-6 rounded-full flex items-center justify-center mr-2 shrink-0 text-xs font-bold"
                >
                  1
                </div>
                <div>
                  <h4 class="text-sm font-medium mb-1">Choose Provider</h4>
                  <p class="text-xs text-muted-foreground">
                    Select a link provider from the available options
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-muted/30 p-4 rounded-lg border border-border/50">
              <div class="flex items-start mb-2">
                <div
                  class="bg-primary/20 text-primary w-6 h-6 rounded-full flex items-center justify-center mr-2 shrink-0 text-xs font-bold"
                >
                  2
                </div>
                <div>
                  <h4 class="text-sm font-medium mb-1">Complete Link</h4>
                  <p class="text-xs text-muted-foreground">
                    Follow the instructions and complete the link
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-muted/30 p-4 rounded-lg border border-border/50">
              <div class="flex items-start mb-2">
                <div
                  class="bg-primary/20 text-primary w-6 h-6 rounded-full flex items-center justify-center mr-2 shrink-0 text-xs font-bold"
                >
                  3
                </div>
                <div>
                  <h4 class="text-sm font-medium mb-1">Get Rewarded</h4>
                  <p class="text-xs text-muted-foreground">
                    Credits are automatically added to your account
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-muted/30 p-4 rounded-lg border border-border/50">
              <div class="flex items-start mb-2">
                <div
                  class="bg-primary/20 text-primary w-6 h-6 rounded-full flex items-center justify-center mr-2 shrink-0 text-xs font-bold"
                >
                  4
                </div>
                <div>
                  <h4 class="text-sm font-medium mb-1">Repeat Daily</h4>
                  <p class="text-xs text-muted-foreground">
                    Complete your daily limit to maximize earnings
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>
