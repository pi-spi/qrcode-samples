const path = require('path');

process.env.NEXT_DISABLE_TURBOPACK = process.env.NEXT_DISABLE_TURBOPACK ?? '1';

/** @type {import('next').NextConfig} */
const nextConfig = {
  experimental: {
    externalDir: true,
  },
  turbopack: {
    root: path.resolve(__dirname),
  },
  webpack: (config) => config,
  generateBuildId: async () => `pi-spi-qr-next-${Date.now().toString(36)}`,
};

module.exports = nextConfig;
